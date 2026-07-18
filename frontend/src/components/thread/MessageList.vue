<script setup lang="ts">
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { THREAD_ROOT_KEY } from '../../constants/dom'
import ChatBackground from './ChatBackground.vue'
import { useConfigStore } from '../../stores/config'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useMessagesStore } from '../../stores/messages'
import { useTypingStore } from '../../stores/typing'
import { useConfirmStore } from '../../stores/confirm'
import { useUiStore } from '../../stores/ui'
import { useMessageScroll } from '../../composables/useMessageScroll'
import { buildMessageListItems } from '../../utils/groupMessageClusters'
import { filterMessagesFromBlockedSenders } from '../../utils/blockMessaging'
import { isParticipantRecord, participantUser } from '../../utils/group'
import { displayUserAvatar, displayUserName } from '../../utils/userDisplay'
import { isPendingMessageId } from '../../utils/outboundMessage'
import { jumpToMessage } from '../../utils/jumpToMessage'
import { formatSystemMessage, isSystemMessage } from '../../utils/systemMessage'
import MessageBubble from './MessageBubble.vue'
import SystemMessage from './SystemMessage.vue'
import MessageDateSeparator from './MessageDateSeparator.vue'
import ScrollToBottomButton from './ScrollToBottomButton.vue'
import TypingBubble from './TypingBubble.vue'
import MessageListSkeleton from '../skeletons/MessageListSkeleton.vue'
import MessageListCompactSkeleton from '../skeletons/MessageListCompactSkeleton.vue'
import MessageOlderSkeleton from '../skeletons/MessageOlderSkeleton.vue'
import MessageLoadError from '../states/MessageLoadError.vue'
import EmptyState from '../states/EmptyState.vue'
import ImageLightbox from '../ui/ImageLightbox.vue'

const configStore = useConfigStore()
const conversationsStore = useConversationsStore()
const messagesStore = useMessagesStore()
const typingStore = useTypingStore()
const contactsStore = useContactsStore()
const confirmStore = useConfirmStore()
const uiStore = useUiStore()
const { messagingBlockedUserIds } = storeToRefs(contactsStore)

const { activeConversation, activeId } = storeToRefs(conversationsStore)
const {
  activeMessages,
  activeLoading,
  activeLoadingOlder,
  activeError,
  pendingScrollToBottom,
} = storeToRefs(messagesStore)

const scrollContainer = ref<HTMLElement | null>(null)
const threadRoot = inject(THREAD_ROOT_KEY, ref<HTMLElement | null>(null))
let markReadTimer: number | null = null

const participantMap = computed(() => {
  const names = new Map<string, string>()
  const avatars = new Map<string, string>()

  activeConversation.value?.relationships.participants.forEach((item) => {
    const user = isParticipantRecord(item) ? participantUser(item) : item
    if (user) {
      names.set(String(user.id), displayUserName(user))
      avatars.set(String(user.id), displayUserAvatar(user, configStore.defaultAvatarUrl))
    }
  })

  activeConversation.value?.relationships.participants_preview?.forEach((item) => {
    const user = participantUser(item)
    if (user) {
      names.set(String(user.id), displayUserName(user))
      avatars.set(String(user.id), displayUserAvatar(user, configStore.defaultAvatarUrl))
    }
  })

  return { names, avatars }
})

const isGroup = computed(
  () => activeConversation.value?.attributes.conversation_type === 'group',
)

const visibleMessages = computed(() => {
  messagingBlockedUserIds.value

  return filterMessagesFromBlockedSenders(
    activeMessages.value,
    (userId) => contactsStore.isMessagingBlocked(userId),
    configStore.user?.id,
  )
})

const daySections = computed(() =>
  buildMessageListItems(visibleMessages.value, configStore.user?.id, isGroup.value).filter(
    (item): item is Extract<typeof item, { kind: 'day' }> => item.kind === 'day',
  ),
)

const typingUsers = computed(() => {
  messagingBlockedUserIds.value

  if (!activeId.value) {
    return []
  }

  return typingStore
    .typingUserIds(activeId.value, configStore.user?.id)
    .filter((userId) => !contactsStore.isMessagingBlocked(userId))
    .map((userId) => ({
      id: userId,
      name: participantMap.value.names.get(userId),
      avatarUrl: participantMap.value.avatars.get(userId),
    }))
})

const isAnyoneTyping = computed(() => typingUsers.value.length > 0)

const { unseenCount, scrollToBottom, notifyNewMessage, bind, isNearBottom } = useMessageScroll(
  scrollContainer,
  {
    onLoadOlder: () => {
      if (!activeId.value || activeLoadingOlder.value || activeLoading.value) {
        return
      }
      return messagesStore.fetchMessages(activeId.value)
    },
    pendingScrollToBottom,
  },
)

async function retry() {
  if (activeId.value) {
    await messagesStore.fetchMessages(activeId.value, true)
  }
}

function onEdit(message: (typeof activeMessages.value)[number]) {
  messagesStore.setEditingMessage(message)
}

function onReply(message: (typeof activeMessages.value)[number]) {
  messagesStore.setReplyTo(message)
}

function onForward(message: (typeof activeMessages.value)[number]) {
  messagesStore.setForwardMessage(message)
  uiStore.openModal('forwardMessage')
}

async function onRemoveForMe(message: (typeof activeMessages.value)[number]) {
  await messagesStore.deleteMessage(message, 'me')
}

async function onRemoveForAll(message: (typeof activeMessages.value)[number]) {
  const confirmed = await confirmStore.confirm({
    title: 'Remove for everyone?',
    message: 'This message will be deleted for all participants.',
    confirmLabel: 'Remove',
    variant: 'danger',
  })

  if (!confirmed) {
    return
  }

  await messagesStore.deleteMessage(message, 'all')
}

function onResend(message: (typeof activeMessages.value)[number]) {
  if (isPendingMessageId(message.id)) {
    void messagesStore.retryOutboundMessage(message.id)
  }
}

function onCancel(message: (typeof activeMessages.value)[number]) {
  if (isPendingMessageId(message.id)) {
    messagesStore.cancelOutboundMessage(message.id)
  }
}

function onJumpTo(messageId: string) {
  jumpToMessage(messageId, scrollContainer.value)
}

function scheduleMarkRead() {
  if (!activeId.value || !isNearBottom.value) {
    return
  }

  if (markReadTimer !== null) {
    window.clearTimeout(markReadTimer)
  }

  markReadTimer = window.setTimeout(() => {
    if (activeId.value && isNearBottom.value) {
      void conversationsStore.markRead(activeId.value)
    }
  }, 400)
}

let lastBottomMessageId: string | null = null

watch(
  () => activeMessages.value.length,
  (newLen, oldLen) => {
    const latest = newLen > 0 ? activeMessages.value[newLen - 1] : null

    if (latest && newLen > oldLen && latest.id !== lastBottomMessageId) {
      const isOwn = String(latest.relationships.sender.data.id) === String(configStore.user?.id)
      notifyNewMessage(isOwn)
    }

    lastBottomMessageId = latest?.id ?? null
    scheduleMarkRead()
  },
)

watch(isNearBottom, (near) => {
  if (near) {
    scheduleMarkRead()
  }
})

watch(
  () => activeId.value,
  async (id, previousId) => {
    if (id && id !== previousId) {
      lastBottomMessageId = activeMessages.value.length > 0
        ? activeMessages.value[activeMessages.value.length - 1].id
        : null
      await nextTick()
      bind()
      scrollToBottom('auto')
    }
  },
)

watch(
  () => activeMessages.value.length > 0 && !activeLoading.value,
  async (ready) => {
    if (!ready) {
      return
    }
    await nextTick()
    bind()
  },
)

onMounted(async () => {
  await nextTick()
  bind()
  if (activeMessages.value.length > 0) {
    scrollToBottom('auto')
  }
})
</script>

<template>
  <div class="chatify:relative chatify:flex chatify:flex-1 chatify:flex-col chatify:overflow-hidden chatify:bg-chatify-panel">
    <ChatBackground />
    <ImageLightbox />

    <MessageListSkeleton
      v-if="activeLoading && activeMessages.length === 0 && !activeError"
      class="chatify:relative chatify:z-10"
    />

    <div
      v-else-if="activeError && activeMessages.length === 0"
      class="chatify:relative chatify:z-10 chatify:flex chatify:flex-1 chatify:flex-col"
    >
      <MessageLoadError @retry="retry" />
      <MessageListCompactSkeleton class="chatify:flex-1" />
    </div>

    <EmptyState
      v-else-if="visibleMessages.length === 0 && activeMessages.length === 0 && !activeError"
      title="No messages yet"
      description="Send a message to start the conversation."
      class="chatify:relative chatify:z-10 chatify:flex-1"
    />

    <template v-else>
      <ul
        ref="scrollContainer"
        class="chatify:relative chatify:z-10 chatify:flex chatify:flex-1 chatify:flex-col chatify:overflow-y-auto chatify:px-4 chatify:py-4 chatify:pb-24"
        @contextmenu.prevent
      >
        <MessageOlderSkeleton v-if="activeLoadingOlder" />

        <li
          v-for="day in daySections"
          :key="day.key"
          class="chatify-message-day"
        >
          <MessageDateSeparator :label="day.label" />

          <template v-for="item in day.items" :key="item.key">
            <div v-if="item.kind === 'cluster'" class="chatify-message-cluster">
              <div class="chatify-message-cluster-row">
                <div class="chatify-message-cluster-avatar">
                  <img
                    v-if="participantMap.avatars.get(item.senderId)"
                    :src="participantMap.avatars.get(item.senderId)"
                    :alt="participantMap.names.get(item.senderId) ?? 'Sender'"
                    class="chatify-message-cluster-avatar-img"
                  />
                </div>
                <div class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:flex-col">
                  <div
                    v-for="entry in item.entries"
                    :key="entry.message.id"
                    :data-message-id="entry.message.id"
                  >
                    <MessageBubble
                      :message="entry.message"
                      :is-own="false"
                      :is-group="isGroup"
                      :sender-name="participantMap.names.get(item.senderId)"
                      :show-sender-name="entry.showSenderName"
                      :cluster-spacing="entry.clusterSpacing"
                      @edit="onEdit(entry.message)"
                      @reply="onReply(entry.message)"
                      @forward="onForward(entry.message)"
                      @remove-for-me="onRemoveForMe(entry.message)"
                      @remove-for-all="onRemoveForAll(entry.message)"
                      @resend="onResend(entry.message)"
                      @cancel="onCancel(entry.message)"
                      @jump-to="onJumpTo"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div
              v-else-if="isSystemMessage(item.message)"
              :data-message-id="item.message.id"
            >
              <SystemMessage
                :label="formatSystemMessage(
                  item.message,
                  (userId) => participantMap.names.get(userId),
                  configStore.user?.id,
                )"
              />
            </div>

            <div v-else :data-message-id="item.message.id">
              <MessageBubble
                :message="item.message"
                :is-own="String(item.message.relationships.sender.data.id) === String(configStore.user?.id)"
                :is-group="isGroup"
                :sender-name="participantMap.names.get(String(item.message.relationships.sender.data.id))"
                :show-sender-name="item.showSenderName"
                :cluster-spacing="item.clusterSpacing"
                @edit="onEdit(item.message)"
                @reply="onReply(item.message)"
                @forward="onForward(item.message)"
                @remove-for-me="onRemoveForMe(item.message)"
                @remove-for-all="onRemoveForAll(item.message)"
                @resend="onResend(item.message)"
                @cancel="onCancel(item.message)"
                @jump-to="onJumpTo"
              />
            </div>
          </template>
        </li>

        <li v-if="isAnyoneTyping">
          <TypingBubble
            align="left"
            :avatar-url="typingUsers[0]?.avatarUrl"
          />
        </li>
      </ul>

      <Teleport v-if="threadRoot" :to="threadRoot">
        <Transition name="chatify-scroll-bottom">
          <ScrollToBottomButton
            v-if="!isNearBottom"
            :count="unseenCount"
            @click="scrollToBottom('smooth')"
          />
        </Transition>
      </Teleport>
    </template>
  </div>
</template>
