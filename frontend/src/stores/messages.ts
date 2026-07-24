import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { CanceledError, type AxiosRequestConfig } from 'axios'
import type { ChatifyMessage, MessageDeletedPayload, MessageReplyPreview } from '../types'
import {
  buildOptimisticMessage,
  isPendingMessageId,
  revokeBlobUrls,
  type OutboundMessageDraft,
} from '../utils/outboundMessage'
import { chatifyT } from '../i18n/nonComponent'
import { filterMessagesFromBlockedSenders, isBlockedSender } from '../utils/blockMessaging'
import { playChatSound } from '../composables/useChatSounds'
import { useConfigStore } from './config'
import { useContactsStore } from './contacts'
import { useConversationsStore } from './conversations'

interface QueueOutboundPayload {
  conversationId: string
  body: string
  attachment?: File
  attachments?: File[]
  reply_to_message_id?: string
  reply_to?: MessageReplyPreview | null
}

interface ConversationMessages {
  items: ChatifyMessage[]
  loading: boolean
  loadingOlder: boolean
  error: string | null
  hasMore: boolean
  nextPage: number
}

export const useMessagesStore = defineStore('messages', () => {
  const byConversation = ref<Record<string, ConversationMessages>>({})
  const replyToMessage = ref<ChatifyMessage | null>(null)
  const editingMessage = ref<ChatifyMessage | null>(null)
  const forwardMessage = ref<ChatifyMessage | null>(null)
  const pendingScrollToBottom = ref(false)
  const outboundDrafts = ref<Record<string, OutboundMessageDraft>>({})
  const outboundAbortControllers = new Map<string, AbortController>()

  const configStore = useConfigStore()
  const contactsStore = useContactsStore()
  const conversationsStore = useConversationsStore()

  function ensureBucket(conversationId: string): ConversationMessages {
    if (!byConversation.value[conversationId]) {
      byConversation.value[conversationId] = {
        items: [],
        loading: false,
        loadingOlder: false,
        error: null,
        hasMore: true,
        nextPage: 1,
      }
    }
    return byConversation.value[conversationId]
  }

  const activeMessages = computed(() => {
    const id = conversationsStore.activeId
    if (!id) {
      return []
    }
    return byConversation.value[id]?.items ?? []
  })

  const activeLoading = computed(() => {
    const id = conversationsStore.activeId
    if (!id) {
      return false
    }
    return byConversation.value[id]?.loading ?? false
  })

  const activeLoadingOlder = computed(() => {
    const id = conversationsStore.activeId
    if (!id) {
      return false
    }
    return byConversation.value[id]?.loadingOlder ?? false
  })

  const activeError = computed(() => {
    const id = conversationsStore.activeId
    if (!id) {
      return null
    }
    return byConversation.value[id]?.error ?? null
  })

  function sortMessages(messages: ChatifyMessage[]): ChatifyMessage[] {
    return [...messages].sort((a, b) => {
      const aTime = a.attributes.created_at ?? ''
      const bTime = b.attributes.created_at ?? ''
      return aTime.localeCompare(bTime)
    })
  }

  function upsertMessage(conversationId: string, message: ChatifyMessage) {
    const bucket = ensureBucket(conversationId)
    const index = bucket.items.findIndex((item) => item.id === message.id)
    if (index >= 0) {
      bucket.items[index] = message
    } else {
      bucket.items.push(message)
    }
    bucket.items = sortMessages(bucket.items)
    conversationsStore.updateLastMessage(conversationId, message)
  }

  function cleanupOutboundDraft(tempId: string) {
    const draft = outboundDrafts.value[tempId]
    if (draft) {
      revokeBlobUrls(draft.blobUrls)
      const next = { ...outboundDrafts.value }
      delete next[tempId]
      outboundDrafts.value = next
    }
  }

  function setMessageLocalStatus(conversationId: string, messageId: string, status: 'sending' | 'failed') {
    const bucket = ensureBucket(conversationId)
    const message = bucket.items.find((item) => item.id === messageId)
    if (message) {
      message.attributes.local_status = status
    }
  }

  function setMessageUploadProgress(conversationId: string, messageId: string, progress: number | null) {
    const bucket = ensureBucket(conversationId)
    const message = bucket.items.find((item) => item.id === messageId)
    if (message) {
      message.attributes.upload_progress = progress
    }
  }

  function hasOutboundAttachment(draft: OutboundMessageDraft): boolean {
    return Boolean(draft.attachment) || Boolean(draft.attachments?.length)
  }

  function replacePendingWithServer(conversationId: string, serverMessage: ChatifyMessage) {
    const bucket = ensureBucket(conversationId)
    const userId = String(serverMessage.relationships.sender.data.id)

    const pendingIndex = bucket.items.findIndex(
      (item) =>
        isPendingMessageId(item.id) &&
        item.attributes.local_status === 'sending' &&
        String(item.relationships.sender.data.id) === userId,
    )

    if (pendingIndex >= 0) {
      cleanupOutboundDraft(bucket.items[pendingIndex].id)
      bucket.items.splice(pendingIndex, 1)
    }

    upsertMessage(conversationId, serverMessage)
  }

  function removeMessage(conversationId: string, messageId: string) {
    if (isPendingMessageId(messageId)) {
      cleanupOutboundDraft(messageId)
    }

    const bucket = ensureBucket(conversationId)
    bucket.items = bucket.items.filter((item) => item.id !== messageId)
  }

  async function dispatchOutbound(tempId: string) {
    const draft = outboundDrafts.value[tempId]
    if (!draft || !configStore.api) {
      return
    }

    setMessageLocalStatus(draft.conversationId, tempId, 'sending')

    const trackProgress = hasOutboundAttachment(draft)
    if (trackProgress) {
      setMessageUploadProgress(draft.conversationId, tempId, 0)
    }

    const controller = new AbortController()
    outboundAbortControllers.set(tempId, controller)

    try {
      const { data } = await configStore.api.sendMessage(
        draft.conversationId,
        {
          body: draft.body,
          attachment: draft.attachment,
          attachments: draft.attachments,
          reply_to_message_id: draft.reply_to_message_id,
        },
        {
          signal: controller.signal,
          onUploadProgress: trackProgress
            ? (event) => {
                const total = event.total ?? 0
                const percent = total > 0 ? Math.round((event.loaded / total) * 100) : 0
                setMessageUploadProgress(draft.conversationId, tempId, percent)
              }
            : undefined,
        },
      )

      outboundAbortControllers.delete(tempId)
      cleanupOutboundDraft(tempId)
      removeMessage(draft.conversationId, tempId)
      upsertMessage(draft.conversationId, data.data)
      pendingScrollToBottom.value = true
      playChatSound('outgoingMessage')
    } catch (error) {
      outboundAbortControllers.delete(tempId)
      if (error instanceof CanceledError || (error as { name?: string })?.name === 'CanceledError') {
        return
      }
      setMessageUploadProgress(draft.conversationId, tempId, null)
      setMessageLocalStatus(draft.conversationId, tempId, 'failed')
    }
  }

  function queueOutboundMessage(payload: QueueOutboundPayload) {
    if (!configStore.user) {
      return null
    }

    const tempId = `pending-${crypto.randomUUID()}`
    const { message, blobUrls } = buildOptimisticMessage(tempId, payload, configStore.user)

    outboundDrafts.value = {
      ...outboundDrafts.value,
      [tempId]: {
        ...payload,
        blobUrls,
      },
    }

    upsertMessage(payload.conversationId, message)
    pendingScrollToBottom.value = true
    replyToMessage.value = null

    void dispatchOutbound(tempId)
    return tempId
  }

  async function retryOutboundMessage(tempId: string) {
    if (!outboundDrafts.value[tempId]) {
      return
    }

    await dispatchOutbound(tempId)
  }

  function cancelOutboundMessage(tempId: string) {
    const draft = outboundDrafts.value[tempId]
    if (!draft) {
      return
    }

    const controller = outboundAbortControllers.get(tempId)
    if (controller) {
      controller.abort()
      outboundAbortControllers.delete(tempId)
    }

    cleanupOutboundDraft(tempId)
    removeMessage(draft.conversationId, tempId)
  }

  async function fetchMessages(conversationId: string, reset = false) {
    if (!configStore.api) {
      return
    }

    const bucket = ensureBucket(conversationId)
    if (bucket.loading || bucket.loadingOlder) {
      return
    }

    if (reset) {
      bucket.items = bucket.items.filter((item) => isPendingMessageId(item.id))
      bucket.nextPage = 1
      bucket.hasMore = true
      bucket.loading = true
    } else {
      if (!bucket.hasMore) {
        return
      }
      bucket.loadingOlder = true
    }

    bucket.error = null

    try {
      const oldestId = bucket.items.find((item) => !isPendingMessageId(item.id))?.id
      const { data } = await configStore.api.getMessages(conversationId, {
        per_page: 30,
        after: !reset && oldestId ? oldestId : undefined,
      })

      const incoming = filterMessagesFromBlockedSenders(
        data.data.reverse(),
        (userId) => contactsStore.isMessagingBlocked(userId),
        configStore.user?.id,
      )
      const existingIds = new Set(bucket.items.map((item) => item.id))
      const merged = [...incoming.filter((item) => !existingIds.has(item.id)), ...bucket.items]
      bucket.items = sortMessages(
        filterMessagesFromBlockedSenders(
          merged,
          (userId) => contactsStore.isMessagingBlocked(userId),
          configStore.user?.id,
        ),
      )

      bucket.hasMore = Boolean(
        data.meta?.current_page &&
          data.meta?.last_page &&
          data.meta.current_page < data.meta.last_page,
      )
      bucket.nextPage += 1

      if (reset) {
        pendingScrollToBottom.value = true
      }
    } catch {
      bucket.error = chatifyT('ui.errors.failed_load_messages')
    } finally {
      bucket.loading = false
      bucket.loadingOlder = false
    }
  }

  async function sendMessage(
    conversationId: string,
    body: string,
    attachment?: File,
    attachments?: File[],
    config?: AxiosRequestConfig,
    options?: { reply_to_message_id?: string },
  ) {
    if (!configStore.api) {
      return null
    }

    const replyToId = options?.reply_to_message_id ?? replyToMessage.value?.id

    const { data } = await configStore.api.sendMessage(
      conversationId,
      {
        body,
        attachment,
        attachments,
        reply_to_message_id: replyToId,
      },
      config,
    )

    upsertMessage(conversationId, data.data)
    replyToMessage.value = null
    pendingScrollToBottom.value = true
    playChatSound('outgoingMessage')
    return data.data
  }

  async function updateMessage(message: ChatifyMessage, body: string) {
    if (!configStore.api) {
      return null
    }

    const { data } = await configStore.api.updateMessage(message.id, body)
    upsertMessage(message.attributes.conversation_id, data.data)
    editingMessage.value = null
    return data.data
  }

  async function deleteMessage(message: ChatifyMessage, scope: 'me' | 'all' = 'all') {
    if (isPendingMessageId(message.id)) {
      cancelOutboundMessage(message.id)
      return
    }

    if (!configStore.api) {
      return
    }

    await configStore.api.deleteMessage(message.id, scope)
    if (scope === 'all') {
      removeMessage(message.attributes.conversation_id, message.id)
    } else {
      removeMessage(message.attributes.conversation_id, message.id)
    }
  }

  async function forwardMessageTo(conversationId: string, message: ChatifyMessage) {
    if (!configStore.api) {
      return null
    }

    const { data } = await configStore.api.forwardMessage(conversationId, message.id)
    if (conversationId === conversationsStore.activeId) {
      upsertMessage(conversationId, data.data)
      pendingScrollToBottom.value = true
    } else {
      conversationsStore.updateLastMessage(conversationId, data.data)
    }
    forwardMessage.value = null
    return data.data
  }

  function handleMessageSent(payload: ChatifyMessage) {
    const conversationId = payload.attributes.conversation_id

    if (payload.attributes.kind === 'system') {
      upsertMessage(conversationId, payload)
      conversationsStore.updateLastMessage(conversationId, payload)
      return
    }

    const userId = configStore.user?.id
    const senderId = payload.relationships.sender.data.id

    if (userId && String(senderId) !== String(userId) && isBlockedSender(senderId, contactsStore.isMessagingBlocked)) {
      return
    }

    if (userId && String(senderId) === String(userId)) {
      replacePendingWithServer(conversationId, payload)
      return
    }

    upsertMessage(conversationId, payload)
  }

  function handleMessageUpdated(payload: ChatifyMessage) {
    upsertMessage(payload.attributes.conversation_id, payload)
  }

  function handleMessageDeleted(payload: MessageDeletedPayload) {
    removeMessage(payload.conversation_id, payload.id)
  }

  function setReplyTo(message: ChatifyMessage | null) {
    replyToMessage.value = message
    editingMessage.value = null
  }

  function setEditingMessage(message: ChatifyMessage | null) {
    editingMessage.value = message
    replyToMessage.value = null
  }

  function setForwardMessage(message: ChatifyMessage | null) {
    forwardMessage.value = message
  }

  function consumeScrollToBottomFlag(): boolean {
    if (!pendingScrollToBottom.value) {
      return false
    }
    pendingScrollToBottom.value = false
    return true
  }

  function requestScrollToBottom() {
    pendingScrollToBottom.value = true
  }

  function clearConversation(conversationId: string) {
    delete byConversation.value[conversationId]
  }

  function syncBlockFilters(refetchActive = false) {
    const viewerId = configStore.user?.id
    if (!viewerId) {
      return
    }

    const next: Record<string, ConversationMessages> = {}

    for (const [conversationId, bucket] of Object.entries(byConversation.value)) {
      next[conversationId] = {
        ...bucket,
        items: filterMessagesFromBlockedSenders(
          bucket.items,
          (userId) => contactsStore.isMessagingBlocked(userId),
          viewerId,
        ),
      }
    }

    byConversation.value = next

    if (refetchActive && conversationsStore.activeId) {
      void fetchMessages(conversationsStore.activeId, true)
    }
  }

  return {
    byConversation,
    replyToMessage,
    editingMessage,
    forwardMessage,
    pendingScrollToBottom,
    activeMessages,
    activeLoading,
    activeLoadingOlder,
    activeError,
    ensureBucket,
    upsertMessage,
    removeMessage,
    queueOutboundMessage,
    retryOutboundMessage,
    cancelOutboundMessage,
    fetchMessages,
    sendMessage,
    updateMessage,
    deleteMessage,
    forwardMessageTo,
    handleMessageSent,
    handleMessageUpdated,
    handleMessageDeleted,
    setReplyTo,
    setEditingMessage,
    setForwardMessage,
    consumeScrollToBottomFlag,
    requestScrollToBottom,
    clearConversation,
    syncBlockFilters,
  }
})
