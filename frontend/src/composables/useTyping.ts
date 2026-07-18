import { onBeforeUnmount, ref } from 'vue'
import { useConfigStore } from '../stores/config'

const TYPING_STOP_MS = 1800

export function useTyping(conversationId: () => string | null) {
  const configStore = useConfigStore()
  const stopTimer = ref<number | null>(null)
  const isTyping = ref(false)

  function clearTimer() {
    if (stopTimer.value !== null) {
      window.clearTimeout(stopTimer.value)
      stopTimer.value = null
    }
  }

  async function sendTypingState(next: boolean) {
    const id = conversationId()
    if (!id || !configStore.api) {
      return
    }

    try {
      await configStore.api.sendTyping(id, next)
    } catch {
    }
  }

  function notifyTyping() {
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
