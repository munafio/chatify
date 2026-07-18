import { onMounted, onUnmounted } from 'vue'
import { useConfigStore } from '../stores/config'

const HEARTBEAT_MS = 25_000

export function usePresence() {
  const configStore = useConfigStore()
  let heartbeatTimer: number | null = null

  async function sendHeartbeat() {
    if (!configStore.api || !configStore.showOnlineStatus || !configStore.broadcastEnabled) {
      return
    }

    if (document.visibilityState !== 'visible') {
      return
    }

    try {
      await configStore.api.sendPresenceHeartbeat()
    } catch {
    }
  }

  async function sendOffline(useKeepalive = false) {
    if (!configStore.api || !configStore.showOnlineStatus) {
      return
    }

    const url = `${configStore.boot?.apiBase}/presence/offline`

    if (useKeepalive) {
      void fetch(url, {
        method: 'POST',
        credentials: 'include',
        keepalive: true,
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': configStore.boot?.csrfToken ?? '',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      return
    }

    try {
      await configStore.api.sendPresenceOffline()
    } catch {
    }
  }

  function stopHeartbeat() {
    if (heartbeatTimer !== null) {
      window.clearInterval(heartbeatTimer)
      heartbeatTimer = null
    }
  }

  function startHeartbeat() {
    stopHeartbeat()

    if (!configStore.showOnlineStatus || !configStore.broadcastEnabled) {
      return
    }

    void sendHeartbeat()
    heartbeatTimer = window.setInterval(() => {
      void sendHeartbeat()
    }, HEARTBEAT_MS)
  }

  function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
      startHeartbeat()
      return
    }

    stopHeartbeat()
    void sendOffline()
  }

  function onPageHide() {
    stopHeartbeat()
    void sendOffline(true)
  }

  function onBeforeUnload() {
    stopHeartbeat()
    void sendOffline(true)
  }

  function onBrowserOnline() {
    if (document.visibilityState === 'visible') {
      startHeartbeat()
    }
  }

  function onBrowserOffline() {
    stopHeartbeat()
    void sendOffline()
  }

  onMounted(() => {
    startHeartbeat()
    document.addEventListener('visibilitychange', onVisibilityChange)
    window.addEventListener('pagehide', onPageHide)
    window.addEventListener('beforeunload', onBeforeUnload)
    window.addEventListener('online', onBrowserOnline)
    window.addEventListener('offline', onBrowserOffline)
  })

  onUnmounted(() => {
    stopHeartbeat()
    document.removeEventListener('visibilitychange', onVisibilityChange)
    window.removeEventListener('pagehide', onPageHide)
    window.removeEventListener('beforeunload', onBeforeUnload)
    window.removeEventListener('online', onBrowserOnline)
    window.removeEventListener('offline', onBrowserOffline)
    void sendOffline()
  })

  return {
    startHeartbeat,
    stopHeartbeat,
    sendOffline,
  }
}
