import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useConnectionStore } from './connection'

describe('useConnectionStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('shows connecting when browser is offline', () => {
    const store = useConnectionStore()
    store.setBrowserOnline(false)

    expect(store.uiState).toBe('connecting')
  })

  it('shows connecting when broadcast is enabled but pusher is not connected', () => {
    const store = useConnectionStore()
    store.setBroadcastEnabled(true)
    store.setPusherState('connecting')

    expect(store.uiState).toBe('connecting')
  })

  it('shows updating while resync is in progress', () => {
    const store = useConnectionStore()
    store.setBrowserOnline(false)
    store.beginUpdating()

    expect(store.uiState).toBe('updating')
  })

  it('returns online when browser and pusher are healthy', () => {
    const store = useConnectionStore()
    store.setBroadcastEnabled(true)
    store.setPusherState('connected')
    store.setBrowserOnline(true)

    expect(store.uiState).toBe('online')
  })
})
