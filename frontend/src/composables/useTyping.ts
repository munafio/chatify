import { onBeforeUnmount, ref } from 'vue'
import { useConfigStore } from '../stores/config'
import { useContactsStore } from '../stores/contacts'
import { useConversationsStore } from '../stores/conversations'

const TYPING_STOP_MS = 1800

export function useTyping(conversationId: () => string | null) {
  const configStore = useConfigStore()
  const contactsStore = useContactsStore()
  const conversationsStore = useConversationsStore()
  const stopTimer = ref<number | null>(null)
  const isTyping = ref(false)

  function clearTimer() {
    if (stopTimer.value !== null) {
      window.clearTimeout(stopTimer.value)
      stopTimer.value = null
    }
  }

  function canSendTyping(): boolean {
    const id = conversationId()
    if (!id) {
      return false
    }

    const conversation = conversationsStore.activeConversation
    if (!conversation || conversation.id !== id) {
      return true
    }

    if (conversation.attributes.conversation_type === 'direct') {
      const otherUser = conversation.relationships.other_user
      if (otherUser && contactsStore.isMessagingBlocked(otherUser.id)) {
        return false
      }
    }

    return true
  }

  async function sendTypingState(next: boolean) {
    const id = conversationId()
    if (!id || !configStore.api || !canSendTyping()) {
      return
    }

    try {
      await configStore.api.sendTyping(id, next)
    } catch {
    }
  }

  function notifyTyping() {
    if (!canSendTyping()) {
      return
    }

    if (!isTyping.value) {
      isTyping.value = true
      void sendTypingState(true)
    }

    clearTimer()
    stopTimer.value = window.setTimeout(() => {
      isTyping.value = false
      void sendTypingState(false)
    }, TYPING_STOP_MS)
  }

  function stopTyping() {
    clearTimer()
    if (isTyping.value) {
      isTyping.value = false
      void sendTypingState(false)
    }
  }

  onBeforeUnmount(stopTyping)

  return {
    notifyTyping,
    stopTyping,
  }
}
