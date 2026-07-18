import { defineComponent } from 'vue'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { usePresence } from './usePresence'
import { useConfigStore } from '../stores/config'

const PresenceHost = defineComponent({
  setup() {
    usePresence()
    return () => null
  },
})

describe('usePresence', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    setActivePinia(createPinia())

    const configStore = useConfigStore()
    configStore.showOnlineStatus = true
    configStore.api = {
      sendPresenceHeartbeat: vi.fn().mockResolvedValue(undefined),
      sendPresenceOffline: vi.fn().mockResolvedValue(undefined),
    } as never
    configStore.boot = {
      apiBase: '/chatify/api',
      csrfToken: 'test-token',
      broadcast: {
        key: 'test-key',
        driver: 'pusher',
      },
    } as never

    Object.defineProperty(document, 'visibilityState', {
      configurable: true,
      get: () => 'visible',
    })
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.restoreAllMocks()
  })

  it('sends heartbeat on mount when presence is enabled', async () => {
    const configStore = useConfigStore()
    mount(PresenceHost)

    await vi.runOnlyPendingTimersAsync()

    expect(configStore.api?.sendPresenceHeartbeat).toHaveBeenCalled()
  })

  it('sends offline and skips heartbeat when tab becomes hidden', async () => {
    const configStore = useConfigStore()
    mount(PresenceHost)

    await vi.runOnlyPendingTimersAsync()
    vi.mocked(configStore.api!.sendPresenceHeartbeat).mockClear()

    Object.defineProperty(document, 'visibilityState', {
      configurable: true,
      get: () => 'hidden',
    })
    document.dispatchEvent(new Event('visibilitychange'))
    await vi.runOnlyPendingTimersAsync()

    expect(configStore.api?.sendPresenceOffline).toHaveBeenCalled()
    expect(configStore.api?.sendPresenceHeartbeat).not.toHaveBeenCalled()
  })

  it('sends offline when the browser goes offline', async () => {
    const configStore = useConfigStore()
    mount(PresenceHost)

    await vi.runOnlyPendingTimersAsync()
    vi.mocked(configStore.api!.sendPresenceOffline).mockClear()

    window.dispatchEvent(new Event('offline'))
    await vi.runOnlyPendingTimersAsync()

    expect(configStore.api?.sendPresenceOffline).toHaveBeenCalled()
  })

  it('restarts heartbeat when the browser comes back online', async () => {
    const configStore = useConfigStore()
    mount(PresenceHost)

    await vi.runOnlyPendingTimersAsync()
    vi.mocked(configStore.api!.sendPresenceHeartbeat).mockClear()

    window.dispatchEvent(new Event('online'))
    await vi.runOnlyPendingTimersAsync()

    expect(configStore.api?.sendPresenceHeartbeat).toHaveBeenCalled()
  })

  it('does not heartbeat when show online status is disabled', async () => {
    const configStore = useConfigStore()
    configStore.showOnlineStatus = false

    mount(PresenceHost)
    await vi.runOnlyPendingTimersAsync()

    expect(configStore.api?.sendPresenceHeartbeat).not.toHaveBeenCalled()
  })
})
