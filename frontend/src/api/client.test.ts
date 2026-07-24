import { describe, expect, it, vi } from 'vitest'
import axios from 'axios'
import { createApiClient, createChatifyApi } from '../api/client'
import type { BootConfig } from '../types'
import { TEST_BOOT_I18N } from '../i18n/bootLocale'

const bootConfig: BootConfig = {
  ...TEST_BOOT_I18N,
  user: {
    type: 'user',
    id: 1,
    attributes: { name: 'Test User', avatar: '/avatar.png' },
  },
  apiBase: 'https://example.test/api/chatify/v1',
  broadcastAuthUrl: 'https://example.test/api/chatify/v1/broadcasting/auth',
  csrfToken: 'test-csrf-token',
  conversationId: null,
  webBase: '/chatify',
  debug: false,
  groupsEnabled: true,
  features: {
    giphy: false,
    colors: true,
    themes: true,
    fonts: true,
    wallpaper: true,
  },
  colors: ['#2180f3'],
  themes: ['classic'],
  fonts: ['system'],
  attachments: {
    maxUploadSize: 150,
    allowedImages: ['png', 'jpg'],
    allowedFiles: ['pdf'],
  },
  broadcast: {
    driver: 'null',
    key: null,
    cluster: null,
    wsHost: null,
    wsPort: 443,
    forceTLS: true,
  },
}

describe('createApiClient', () => {
  it('configures axios with credentials and csrf token', () => {
    const createSpy = vi.spyOn(axios, 'create')
    createApiClient(bootConfig)

    expect(createSpy).toHaveBeenCalledWith(
      expect.objectContaining({
        baseURL: bootConfig.apiBase,
        withCredentials: true,
        headers: expect.objectContaining({
          'X-CSRF-TOKEN': 'test-csrf-token',
          Accept: 'application/json',
        }),
      }),
    )
  })
})

describe('createChatifyApi', () => {
  it('exposes conversation and message endpoints', () => {
    const client = axios.create({ baseURL: bootConfig.apiBase })
    const get = vi.spyOn(client, 'get').mockResolvedValue({ data: { data: [] } })
    const api = createChatifyApi(client)

    void api.getConversations()
    void api.getMessages('conv-1')

    expect(get).toHaveBeenCalledWith('/conversations', { params: undefined })
    expect(get).toHaveBeenCalledWith('/conversations/conv-1/messages', { params: undefined })
  })
})
