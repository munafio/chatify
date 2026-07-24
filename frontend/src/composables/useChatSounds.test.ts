import { describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useConfigStore } from '../stores/config'
import { useChatSounds } from './useChatSounds'
import type { BootConfig } from '../types'
import { TEST_BOOT_I18N } from '../i18n/bootLocale'

const boot = {
  ...TEST_BOOT_I18N,
  user: { type: 'user', id: 1, attributes: { name: 'Me', avatar: '' } },
  apiBase: '/api',
  broadcastAuthUrl: '/auth',
  csrfToken: 'token',
  conversationId: null,
  webBase: '/chatify',
  debug: false,
  groupsEnabled: true,
  colors: [],
  attachments: { maxUploadSize: 1, allowedImages: [], allowedFiles: [] },
  broadcast: { driver: 'null', key: null, cluster: null, wsHost: null, wsPort: 443, forceTLS: true },
  sounds: {
    enabled: true,
    incomingMessage: { enabled: true, url: '/incoming.wav' },
    outgoingMessage: { enabled: false, url: '/outgoing.wav' },
    typing: { enabled: true, url: null },
  },
} as BootConfig

describe('useChatSounds', () => {
  it('respects master and per-event toggles', () => {
    setActivePinia(createPinia())
    const configStore = useConfigStore()
    configStore.init(boot)
    const { isEventEnabled } = useChatSounds()

    expect(isEventEnabled('incomingMessage')).toBe(true)
    expect(isEventEnabled('outgoingMessage')).toBe(false)
    expect(isEventEnabled('typing')).toBe(false)
  })
})
