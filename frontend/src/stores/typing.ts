import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useTypingStore = defineStore('typing', () => {
  const byConversation = ref<Record<string, Record<string, boolean>>>({})

  function setTyping(conversationId: string, userId: number | string, isTyping: boolean) {
    const key = String(userId)
    const bucket = { ...(byConversation.value[conversationId] ?? {}) }

    if (isTyping) {
      bucket[key] = true
    } else {
      delete bucket[key]
    }

    byConversation.value = {
      ...byConversation.value,
      [conversationId]: bucket,
    }
  }

  function clearConversation(conversationId: string) {
    const next = { ...byConversation.value }
    delete next[conversationId]
    byConversation.value = next
  }

  function typingUserIds(conversationId: string, excludeUserId?: number | string): string[] {
    const bucket = byConversation.value[conversationId] ?? {}
    return Object.keys(bucket).filter((id) => {
      if (!bucket[id]) {
        return false
      }
      return excludeUserId === undefined || String(id) !== String(excludeUserId)
    })
  }

  return {
    byConversation,
    setTyping,
    clearConversation,
    typingUserIds,
  }
})
