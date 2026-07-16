<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import ChatBackground from './ChatBackground.vue'
import { useConfigStore } from '../../stores/config'
import { useConversationsStore } from '../../stores/conversations'
import { useMessagesStore } from '../../stores/messages'
import { useTypingStore } from '../../stores/typing'
import { useUiStore } from '../../stores/ui'
import { useMessageScroll } from '../../composables/useMessageScroll'
import { groupMessagesByDate } from '../../utils/groupMessages'
import { isPendingMessageId } from '../../utils/outboundMessage'
import MessageBubble from './MessageBubble.vue'
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
const uiStore = useUiStore()

const { activeConversation, activeId } = storeToRefs(conversationsStore)
const {
  activeMessages,
  activeLoading,
  activeLoadingOlder,
  activeError,
  pendingScrollToBottom,
} = storeToRefs(messagesStore)

const scrollContainer = ref<HTMLElement | null>(null)
let markReadTimer: number | null = null

const participantMap = computed(() => {
  const map = new Map<string, string>()
  activeConversation.value?.relationships.participants.forEach((user) => {
    map.set(String(user.id), user.attributes.name)
  })
  return map
})

const isGroup = computed(
  () => activeConversation.value?.attributes.conversation_type === 'group',
)

const groupedItems = computed(() => groupMessagesByDate(activeMessages.value))

const isAnyoneTyping = computed(() => {
  if (!activeId.value) {
    return false
  }
  return typingStore.typingUserIds(activeId.value, configStore.user?.id).length > 0
})

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

watch(
  () => activeMessages.value.length,
  (newLen, oldLen) => {
    if (newLen > oldLen && activeMessages.value.length > 0) {
      const latest = activeMessages.value[activeMessages.value.length - 1]
      const isOwn = String(latest.relationships.sender.data.id) === String(configStore.user?.id)
      notifyNewMessage(isOwn)
    }
    scheduleMarkRead()
  },
)

watch(isAnyoneTyping, (typing) => {
  if (typing && isNearBottom.value) {
    void nextTick(() => scrollToBottom('smooth'))
  }
})

watch(isNearBottom, (near) => {
  if (near) {
    scheduleMarkRead()
  }
})

watch(
  () => activeId.value,
  async (id, previousId) => {
    if (id && id !== previousId) {
      await nextTick()
      bind()
      scrollToBottom('auto')
    }
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
      v-else-if="activeMessages.length === 0 && !activeError"
      title="No messages yet"
      description="Send a message to start the conversation."
      class="chatify:relative chatify:z-10 chatify:flex-1"
    />

    <template v-else>
      <ul
        ref="scrollContainer"
        class="chatify:relative chatify:z-10 chatify:flex chatify:flex-1 chatify:flex-col chatify:gap-2 chatify:overflow-y-auto chatify:px-4 chatify:py-4"
      >
        <MessageOlderSkeleton v-if="activeLoadingOlder" />

        <template v-for="item in groupedItems" :key="item.key">
          <li v-if="item.kind === 'date'">
            <MessageDateSeparator :label="item.label" />
          </li>
          <li v-else>
            <MessageBubble
              :message="item.message"
              :is-own="String(item.message.relationships.sender.data.id) === String(configStore.user?.id)"
              :is-group="isGroup"
              :sender-name="participantMap.get(String(item.message.relationships.sender.data.id))"
              @edit="onEdit(item.message)"
              @reply="onReply(item.message)"
              @forward="onForward(item.message)"
              @remove-for-me="onRemoveForMe(item.message)"
              @remove-for-all="onRemoveForAll(item.message)"
              @resend="onResend(item.message)"
              @cancel="onCancel(item.message)"
            />
          </li>
        </template>

        <li v-if="isAnyoneTyping">
          <TypingBubble align="left" />
        </li>
      </ul>

      <ScrollToBottomButton
        v-if="!isNearBottom"
        :count="unseenCount"
        @click="scrollToBottom('smooth')"
      />
    </template>
  </div>
</template>
