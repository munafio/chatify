import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ChatifyConversation } from '../types'
import { participantUser } from '../utils/group'
import { sortConversations } from '../utils/sortConversations'
import { isSavedConversation } from '../utils/format'
import { maskUserIdentity, sanitizeConversationIdentity } from '../utils/userDisplay'
import { useConfigStore } from './config'
import { useContactsStore } from './contacts'
import { useMessagesStore } from './messages'

export const useConversationsStore = defineStore('conversations', () => {
  const items = ref<ChatifyConversation[]>([])
  const activeId = ref<string | null>(null)
  const loading = ref(false)
  const loadFailed = ref(false)
  const searchQuery = ref('')
  const sidebarTab = ref<'chats' | 'favorites'>('chats')

  const configStore = useConfigStore()

  const activeConversation = computed(() =>
    items.value.find((item) => String(item.id) === String(activeId.value)) ?? null,
  )

  const filteredItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) {
      return items.value
    }

    return items.value.filter((conversation) => {
      const name =
        isSavedConversation(conversation)
          ? conversation.attributes.saved_title ?? conversation.attributes.name ?? configStore.savedMessagesTitle
          : conversation.attributes.conversation_type === 'group'
            ? conversation.attributes.name ?? ''
            : conversation.relationships.other_user?.attributes.name ?? ''
      return name.toLowerCase().includes(q)
    })
  })

  const pinnedItems = computed(() =>
    filteredItems.value.filter(
      (conversation) => conversation.attributes.is_pinned && !isSavedConversation(conversation),
    ),
  )

  const recentItems = computed(() =>
    filteredItems.value.filter(
      (conversation) => !conversation.attributes.is_pinned && !isSavedConversation(conversation),
    ),
  )

  const savedItems = computed(() =>
    filteredItems.value.filter((conversation) => isSavedConversation(conversation)),
  )

  function applySort() {
    replaceItems([...items.value])
  }

  function sanitizeForViewer(conversation: ChatifyConversation): ChatifyConversation {
    const contactsStore = useContactsStore()

    return sanitizeConversationIdentity(conversation, {
      defaultAvatarUrl: configStore.defaultAvatarUrl,
      isBlockedByMe: (userId) => contactsStore.isBlockedByMe(userId),
      isMessagingBlocked: (userId) => contactsStore.isMessagingBlocked(userId),
    })
  }

  function cloneConversation(conversation: ChatifyConversation): ChatifyConversation {
    const lastMessage = conversation.relationships.last_message

    return {
      ...conversation,
      attributes: { ...conversation.attributes },
      relationships: {
        ...conversation.relationships,
        last_message: lastMessage
          ? {
              ...lastMessage,
              attributes: { ...lastMessage.attributes },
              relationships: {
                ...lastMessage.relationships,
                sender: {
                  ...lastMessage.relationships.sender,
                  data: { ...lastMessage.relationships.sender.data },
                },
              },
            }
          : null,
        other_user: conversation.relationships.other_user
          ? {
              ...conversation.relationships.other_user,
              attributes: { ...conversation.relationships.other_user.attributes },
            }
          : null,
      },
    }
  }

  function replaceItems(next: ChatifyConversation[]) {
    items.value = sortConversations(next)
  }

  function mergeConversationState(
    local: ChatifyConversation,
    remote: ChatifyConversation,
  ): ChatifyConversation {
    const localMessage = local.relationships.last_message
    const remoteMessage = remote.relationships.last_message
    const localMessageAt = localMessage?.attributes.created_at ?? ''
    const remoteMessageAt = remoteMessage?.attributes.created_at ?? ''

    if (localMessage && (!remoteMessage || localMessageAt.localeCompare(remoteMessageAt) >= 0)) {
      return {
        ...remote,
        attributes: {
          ...remote.attributes,
          unread_count: Math.max(local.attributes.unread_count, remote.attributes.unread_count),
          updated_at: local.attributes.updated_at ?? remote.attributes.updated_at,
        },
        relationships: {
          ...remote.relationships,
          last_message: localMessage,
        },
      }
    }

    return remote
  }

  function mergeFetchedConversations(fetched: ChatifyConversation[]) {
    const sanitizedFetched = fetched.map((conversation) => sanitizeForViewer(conversation))
    const fetchedById = new Map(sanitizedFetched.map((conversation) => [String(conversation.id), conversation]))
    const merged: ChatifyConversation[] = []

    for (const remote of sanitizedFetched) {
      const local = items.value.find((item) => String(item.id) === String(remote.id))
      merged.push(local ? mergeConversationState(local, remote) : remote)
    }

    for (const local of items.value) {
      if (!fetchedById.has(String(local.id))) {
        merged.push(local)
      }
    }

    items.value = sortConversations(merged)
  }

  function upsert(conversation: ChatifyConversation) {
    const sanitized = sanitizeForViewer(cloneConversation(conversation))
    const index = items.value.findIndex((item) => String(item.id) === String(sanitized.id))

    if (index >= 0) {
      const next = [...items.value]
      next[index] = sanitized
      replaceItems(next)
      return
    }

    replaceItems([sanitized, ...items.value])
  }

  function upsertInboxItem(conversation: ChatifyConversation) {
    upsert(conversation)
  }

  function bumpConversationToTop(_conversationId: string) {
    replaceItems([...items.value])
  }

  function updateLastMessage(conversationId: string, message: ChatifyConversation['relationships']['last_message']) {
    const index = items.value.findIndex((item) => String(item.id) === String(conversationId))
    if (index < 0) {
      return
    }

    const conversation = items.value[index]
    const next = [...items.value]
    next[index] = {
      ...conversation,
      attributes: {
        ...conversation.attributes,
        updated_at: message?.attributes.created_at ?? conversation.attributes.updated_at,
      },
      relationships: {
        ...conversation.relationships,
        last_message: message,
      },
    }
    replaceItems(next)
  }

  function updateUnreadCount(conversationId: string, count: number) {
    const index = items.value.findIndex((item) => String(item.id) === String(conversationId))
    if (index < 0) {
      return
    }

    const conversation = items.value[index]
    const next = [...items.value]
    next[index] = {
      ...conversation,
      attributes: {
        ...conversation.attributes,
        unread_count: count,
      },
    }
    replaceItems(next)
  }

  function updateParticipants(
    conversationId: string,
    payload: {
      participant_count: number
      participants?: ChatifyConversation['relationships']['participants']
      participants_preview?: ChatifyConversation['relationships']['participants_preview']
    },
  ) {
    const conversation = items.value.find((item) => item.id === conversationId)
    if (conversation) {
      if (payload.participants) {
        conversation.relationships.participants = payload.participants
      }
      if (payload.participants_preview) {
        conversation.relationships.participants_preview = payload.participants_preview
      }
      conversation.attributes.participant_count = payload.participant_count
    }
  }

  function updateGroupDetails(conversation: ChatifyConversation) {
    upsert(conversation)
  }

  function removeConversation(conversationId: string) {
    const index = items.value.findIndex((item) => item.id === conversationId)
    if (index >= 0) {
      items.value.splice(index, 1)
    }

    if (activeId.value === conversationId) {
      activeId.value = null
    }
  }

  function handleMembershipRevoked(conversationId: string) {
    removeConversation(conversationId)
  }

  async function fetchAll() {
    if (!configStore.api) {
      return
    }

    loading.value = true
    loadFailed.value = false

    try {
      const { data } = await configStore.api.getConversations()
      mergeFetchedConversations(data.data)
    } catch {
      loadFailed.value = true
    } finally {
      loading.value = false
    }
  }

  async function select(id: string) {
    activeId.value = id
    if (!configStore.api) {
      return
    }

    try {
      const { data } = await configStore.api.getConversation(id)
      upsert(data.data)
    } catch {
    }
  }

  async function startDirect(userId: number | string) {
    if (!configStore.api) {
      return null
    }

    const { data } = await configStore.api.createDirectConversation(userId)
    upsert(data.data)
    activeId.value = data.data.id
    sidebarTab.value = 'chats'
    return data.data
  }

  async function createGroup(name: string, userIds: Array<number | string>) {
    if (!configStore.api) {
      return null
    }

    const { data } = await configStore.api.createGroupConversation(name, userIds)
    upsert(data.data)
    activeId.value = data.data.id
    return data.data
  }

  async function markRead(conversationId: string) {
    if (!configStore.api) {
      return
    }

    await configStore.api.markConversationRead(conversationId)
    updateUnreadCount(conversationId, 0)
  }

  async function togglePin(conversationId: string, pinned?: boolean) {
    if (!configStore.api) {
      return
    }

    const conversation = items.value.find((item) => item.id === conversationId)
    const nextPinned = pinned ?? !conversation?.attributes.is_pinned

    const { data } = await configStore.api.pinConversation(conversationId, nextPinned)
    upsert(data.data)
  }

  async function reorderPinned(conversationIds: string[]) {
    if (!configStore.api) {
      return
    }

    await configStore.api.updatePinOrder(conversationIds)

    conversationIds.forEach((conversationId, index) => {
      const conversation = items.value.find((item) => item.id === conversationId)
      if (conversation) {
        conversation.attributes.is_pinned = true
        conversation.attributes.pin_order = index
      }
    })

    applySort()
  }

  async function clearSavedMessages(conversationId: string) {
    if (!configStore.api) {
      return
    }

    const { data } = await configStore.api.clearConversation(conversationId)
    upsert(data.data)
    useMessagesStore().clearConversation(conversationId)
  }

  function handleInboxUpdate(conversation: ChatifyConversation, _currentUserId: number | string) {
    const sanitized = sanitizeForViewer(cloneConversation(conversation))

    if (String(activeId.value) === String(sanitized.id)) {
      sanitized.attributes.unread_count = 0
    }

    upsertInboxItem(sanitized)
  }

  function resanitizeAll() {
    items.value = items.value.map((conversation) => sanitizeForViewer(conversation))
  }

  async function refreshDirectConversationForBlock(
    blockerId: number | string,
    blockedUserId: number | string,
  ) {
    if (!configStore.api || !configStore.user) {
      return
    }

    const currentUserId = String(configStore.user.id)
    const otherUserId = currentUserId === String(blockerId) ? blockedUserId : blockerId

    const conversation = items.value.find(
      (item) =>
        item.attributes.conversation_type === 'direct'
        && item.relationships.other_user
        && String(item.relationships.other_user.id) === String(otherUserId),
    )

    if (!conversation) {
      return
    }

    try {
      const { data } = await configStore.api.getConversation(conversation.id)
      upsert(data.data)
    } catch {
    }
  }

  function setSearchQuery(query: string) {
    searchQuery.value = query
  }

  function setSidebarTab(tab: 'chats' | 'favorites') {
    sidebarTab.value = tab
  }

  function clearActive() {
    activeId.value = null
  }

  function applyIdentityMaskForUser(userId: number | string, defaultAvatarUrl: string) {
    const targetId = String(userId)

    items.value = items.value.map((conversation) => {
      let next = conversation
      const otherUser = conversation.relationships.other_user

      if (otherUser && String(otherUser.id) === targetId) {
        next = {
          ...next,
          relationships: {
            ...next.relationships,
            other_user: maskUserIdentity(otherUser, defaultAvatarUrl),
          },
        }
      }

      if (next.attributes.created_by && String(next.attributes.created_by.id) === targetId) {
        next = {
          ...next,
          attributes: {
            ...next.attributes,
            created_by: {
              ...next.attributes.created_by,
              name: 'Unknown User',
            },
          },
        }
      }

      if (next.relationships.participants_preview?.some((participant) => {
        const user = participantUser(participant)
        return user && String(user.id) === targetId
      })) {
        next = {
          ...next,
          relationships: {
            ...next.relationships,
            participants_preview: next.relationships.participants_preview?.map((participant) => {
              const user = participantUser(participant)
              if (user && String(user.id) === targetId) {
                return {
                  ...participant,
                  relationships: {
                    ...participant.relationships,
                    user: maskUserIdentity(user, defaultAvatarUrl),
                  },
                }
              }

              return participant
            }) ?? null,
          },
        }
      }

      return next
    })
  }

  return {
    items,
    activeId,
    loading,
    loadFailed,
    searchQuery,
    sidebarTab,
    activeConversation,
    filteredItems,
    pinnedItems,
    recentItems,
    savedItems,
    upsert,
    upsertInboxItem,
    bumpConversationToTop,
    updateLastMessage,
    updateUnreadCount,
    updateParticipants,
    updateGroupDetails,
    handleInboxUpdate,
    handleMembershipRevoked,
    removeConversation,
    fetchAll,
    select,
    startDirect,
    createGroup,
    markRead,
    togglePin,
    reorderPinned,
    clearSavedMessages,
    setSearchQuery,
    setSidebarTab,
    clearActive,
    applyIdentityMaskForUser,
    refreshDirectConversationForBlock,
    resanitizeAll,
  }
})
