import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useConfigStore } from './config'

export const usePresenceStore = defineStore('presence', () => {
  const onlineByUserId = ref<Record<string, boolean>>({})

  function setOnline(userId: number | string, isOnline: boolean) {
    onlineByUserId.value = {
      ...onlineByUserId.value,
      [String(userId)]: isOnline,
    }
  }

  function isOnline(userId: number | string): boolean {
    return onlineByUserId.value[String(userId)] === true
  }

  function canShowPresence(): boolean {
    const configStore = useConfigStore()
    return configStore.showOnlineStatus
  }

  function visibleOnline(userId: number | string): boolean {
    if (!canShowPresence()) {
      return false
    }

    return isOnline(userId)
  }

  return {
    onlineByUserId,
    setOnline,
    isOnline,
    canShowPresence,
    visibleOnline,
  }
})
