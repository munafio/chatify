<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue'
import type Echo from 'laravel-echo'
import type {
  BootConfig,
  ChatifyConversation,
  ChatifyMessage,
  ConversationReadPayload,
  GroupParticipantsChangedPayload,
  MessageDeletedPayload,
  UserTypingPayload,
} from '../types'
import { bindEchoReconnect, subscribeToConversation, subscribeToUserInbox, useEcho } from '../composables/useEcho'
import { useContactsStore } from '../stores/contacts'
import { useConversationsStore } from '../stores/conversations'
import { useMessagesStore } from '../stores/messages'
import { useTypingStore } from '../stores/typing'
import ChatifyApp from './layout/ChatifyApp.vue'
import ContactInfoModal from './modals/ContactInfoModal.vue'
import CreateGroupModal from './modals/CreateGroupModal.vue'
import ForwardMessageModal from './modals/ForwardMessageModal.vue'
import GroupInfoModal from './modals/GroupInfoModal.vue'
import NewChatModal from './modals/NewChatModal.vue'
import SettingsModal from './modals/SettingsModal.vue'
import ToastHost from './ui/ToastHost.vue'

const props = defineProps<{
  config: BootConfig
}>()

const conversationsStore = useConversationsStore()
const messagesStore = useMessagesStore()
const contactsStore = useContactsStore()
const typingStore = useTypingStore()

const echo = useEcho(props.config)
let unsubscribeConversation: (() => void) | null = null
let unsubscribeInbox: (() => void) | null = null
let unsubscribeReconnect: (() => void) | null = null

function bindEcho(conversationId: string | null) {
  unsubscribeConversation?.()
  unsubscribeConversation = null

  if (!conversationId) {
    return
  }

  unsubscribeConversation = subscribeToConversation(echo as Echo<'pusher'> | null, conversationId, {
    onMessageSent: (payload) => {
      const message = payload as ChatifyMessage
      messagesStore.handleMessageSent(message)
      if (conversationId === conversationsStore.activeId) {
        messagesStore.requestScrollToBottom()
      }
    },
    onMessageUpdated: (payload) => messagesStore.handleMessageUpdated(payload as ChatifyMessage),
    onMessageDeleted: (payload) => messagesStore.handleMessageDeleted(payload as MessageDeletedPayload),
    onConversationRead: (payload) => {
      const data = payload as ConversationReadPayload
      if (String(data.user_id) !== String(props.config.user.id)) {
        messagesStore.byConversation[data.conversation_id]?.items.forEach((message) => {
          if (String(message.relationships.sender.data.id) === String(props.config.user.id)) {
            message.attributes.read = true
          }
        })
      }
    },
    onGroupParticipantsChanged: (payload) => {
      const data = payload as GroupParticipantsChangedPayload
      conversationsStore.updateParticipants(data.conversation_id, data.participants, data.participant_count)
    },
    onUserTyping: (payload) => {
      const data = payload as UserTypingPayload
      typingStore.setTyping(data.conversation_id, data.user_id, data.is_typing)
    },
  })
}

function onVisibilityChange() {
  if (document.visibilityState === 'visible') {
    void conversationsStore.fetchAll()
  }
}

onMounted(async () => {
  unsubscribeInbox = subscribeToUserInbox(
    echo as Echo<'pusher'> | null,
    props.config.user.id,
    (payload) => {
      conversationsStore.handleInboxUpdate(payload as ChatifyConversation, props.config.user.id)
    },
  )

  unsubscribeReconnect = bindEchoReconnect(echo as Echo<'pusher'> | null, () => {
    void conversationsStore.fetchAll()
    if (conversationsStore.activeId) {
      void messagesStore.fetchMessages(conversationsStore.activeId, true)
    }
  })

  document.addEventListener('visibilitychange', onVisibilityChange)

  await Promise.all([
    conversationsStore.fetchAll(),
    contactsStore.fetchFavorites(),
  ])

  if (props.config.conversationId) {
    await conversationsStore.select(props.config.conversationId)
    await messagesStore.fetchMessages(props.config.conversationId, true)
    bindEcho(props.config.conversationId)
  }
})

onUnmounted(() => {
  unsubscribeConversation?.()
  unsubscribeInbox?.()
  unsubscribeReconnect?.()
  document.removeEventListener('visibilitychange', onVisibilityChange)
})

watch(
  () => conversationsStore.activeId,
  async (id, previousId) => {
    if (previousId) {
      typingStore.clearConversation(previousId)
    }

    bindEcho(id)

    if (id) {
      await messagesStore.fetchMessages(id, true)
      await conversationsStore.markRead(id)
    }
  },
)
</script>

<template>
  <ChatifyApp />
  <ContactInfoModal />
  <GroupInfoModal />
  <CreateGroupModal />
  <NewChatModal />
  <ForwardMessageModal />
  <SettingsModal />
  <ToastHost />
</template>
