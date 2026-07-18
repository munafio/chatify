import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ConnectionUiState } from '../utils/connectionLabel'

export type PusherConnectionState = 'connected' | 'connecting' | 'unavailable' | 'failed' | 'disconnected' | string

export const useConnectionStore = defineStore('connection', () => {
  const browserOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
  const pusherState = ref<PusherConnectionState | null>(null)
  const broadcastEnabled = ref(false)
  const updating = ref(false)

  const uiState = computed<ConnectionUiState>(() => {
    if (updating.value) {
      return 'updating'
    }

    if (!browserOnline.value) {
      return 'connecting'
    }

    if (broadcastEnabled.value && pusherState.value !== null && pusherState.value !== 'connected') {
      return 'connecting'
    }

    return 'online'
  })

  function setBrowserOnline(online: boolean) {
    browserOnline.value = online
  }

  function setBroadcastEnabled(enabled: boolean) {
    broadcastEnabled.value = enabled
  }

  function setPusherState(state: PusherConnectionState) {
    pusherState.value = state
  }

  function beginUpdating() {
    updating.value = true
  }

  function finishUpdating() {
    updating.value = false
  }

  return {
    browserOnline,
    pusherState,
    broadcastEnabled,
    updating,
    uiState,
    setBrowserOnline,
    setBroadcastEnabled,
    setPusherState,
    beginUpdating,
    finishUpdating,
  }
})
