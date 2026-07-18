import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'
import type {
  BootConfig,
  ChatifyConversation,
  ChatifyMessage,
  ChatifyParticipant,
  ChatifyUser,
  GroupPermissionKey,
  LinkPreview,
  PaginatedResponse,
  SharedAttachment,
  SingleResponse,
  UserSettings,
} from '../types'

export type ApiClient = AxiosInstance

export function createApiClient(config: BootConfig): ApiClient {
  const client = axios.create({
    baseURL: config.apiBase,
    withCredentials: true,
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': config.csrfToken,
    },
  })

  return client
}

export function createChatifyApi(client: ApiClient) {
  return {
    getConversations(params?: { per_page?: number }) {
      return client.get<PaginatedResponse<ChatifyConversation>>('/conversations', { params })
    },

    getConversation(id: string) {
      return client.get<SingleResponse<ChatifyConversation>>(`/conversations/${id}`)
    },

    createDirectConversation(userId: number | string) {
      return client.post<SingleResponse<ChatifyConversation>>('/conversations/direct', {
        user_id: userId,
      })
    },

    createGroupConversation(name: string, userIds: Array<number | string>) {
      return client.post<SingleResponse<ChatifyConversation>>('/conversations/group', {
        name,
        user_ids: userIds,
      })
    },

    updateConversation(id: string, payload: { name?: string; description?: string | null }) {
      return client.patch<SingleResponse<ChatifyConversation>>(`/conversations/${id}`, payload)
    },

    uploadGroupAvatar(
      id: string,
      file: File,
      config?: Pick<AxiosRequestConfig, 'onUploadProgress'>,
    ) {
      const form = new FormData()
      form.append('avatar', file)
      return client.post<SingleResponse<ChatifyConversation>>(`/conversations/${id}/avatar`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
        ...config,
      })
    },

    deleteConversation(id: string) {
      return client.delete(`/conversations/${id}`)
    },

    markConversationRead(id: string) {
      return client.post(`/conversations/${id}/read`)
    },

    sendTyping(conversationId: string, isTyping: boolean) {
      return client.post(`/conversations/${conversationId}/typing`, { is_typing: isTyping })
    },

    addParticipants(id: string, userIds: Array<number | string>) {
      return client.post<SingleResponse<ChatifyConversation>>(`/conversations/${id}/participants`, {
        user_ids: userIds,
      })
    },

    removeParticipant(conversationId: string, userId: number | string) {
      return client.delete(`/conversations/${conversationId}/participants/${userId}`)
    },

    leaveGroup(id: string) {
      return client.post(`/conversations/${id}/leave`)
    },

    getMembers(conversationId: string, params?: { page?: number; per_page?: number; search?: string }) {
      return client.get<PaginatedResponse<ChatifyParticipant>>(`/conversations/${conversationId}/participants`, {
        params,
      })
    },

    updateMemberRole(
      conversationId: string,
      userId: number | string,
      payload: {
        role: 'admin' | 'moderator' | 'member'
        permissions?: Partial<Record<GroupPermissionKey, boolean>> | null
      },
    ) {
      return client.patch<SingleResponse<ChatifyConversation>>(
        `/conversations/${conversationId}/participants/${userId}`,
        payload,
      )
    },

    transferOwnership(conversationId: string, userId: number | string) {
      return client.post<SingleResponse<ChatifyConversation>>(
        `/conversations/${conversationId}/transfer-ownership`,
        { user_id: userId },
      )
    },

    getMessages(conversationId: string, params?: { per_page?: number; after?: string }) {
      return client.get<PaginatedResponse<ChatifyMessage>>(
        `/conversations/${conversationId}/messages`,
        { params },
      )
    },

    sendMessage(
      conversationId: string,
      payload: { body?: string; attachment?: File; attachments?: File[]; reply_to_message_id?: string },
      config?: AxiosRequestConfig,
    ) {
      const form = new FormData()
      if (payload.body) {
        form.append('body', payload.body)
      }
      if (payload.attachment) {
        form.append('attachment', payload.attachment)
      }
      if (payload.attachments?.length) {
        payload.attachments.forEach((file) => form.append('attachments[]', file))
      }
      if (payload.reply_to_message_id) {
        form.append('reply_to_message_id', payload.reply_to_message_id)
      }
      return client.post<SingleResponse<ChatifyMessage>>(
        `/conversations/${conversationId}/messages`,
        form,
        {
          headers: { 'Content-Type': 'multipart/form-data' },
          ...config,
        },
      )
    },

    updateMessage(id: string, body: string) {
      return client.patch<SingleResponse<ChatifyMessage>>(`/messages/${id}`, { body })
    },

    deleteMessage(id: string, scope: 'me' | 'all' = 'all') {
      return client.delete(`/messages/${id}`, { params: { scope } })
    },

    forwardMessage(conversationId: string, messageId: string) {
      return client.post<SingleResponse<ChatifyMessage>>(`/conversations/${conversationId}/forward`, {
        message_id: messageId,
      })
    },

    searchContacts(q: string, params?: { per_page?: number }) {
      return client.get<PaginatedResponse<ChatifyUser>>('/contacts/search', {
        params: { q, ...params },
      })
    },

    getSettings() {
      return client.get<SingleResponse<UserSettings>>('/settings')
    },

    updateSettings(payload: FormData, config?: AxiosRequestConfig) {
      return client.post<SingleResponse<UserSettings>>('/settings/avatar', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
        ...config,
      })
    },

    patchSettings(payload: {
      dark_mode?: boolean
      reset_avatar?: boolean
      theme_preferences?: Record<string, unknown>
    }) {
      return client.patch<SingleResponse<UserSettings>>('/settings', payload)
    },

    uploadChatBackground(payload: FormData) {
      return client.post<SingleResponse<UserSettings>>('/settings/chat-background', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    },

    getFavorites() {
      return client.get<PaginatedResponse<ChatifyUser>>('/favorites')
    },

    toggleFavorite(userId: number | string) {
      return client.post(`/favorites/${userId}`)
    },

    getAttachments(
      conversationId: string,
      params?: { type?: 'media' | 'docs' | 'links'; page?: number; per_page?: number },
    ) {
      return client.get<PaginatedResponse<SharedAttachment>>(`/conversations/${conversationId}/attachments`, {
        params,
      })
    },

    searchMessages(
      conversationId: string,
      q: string,
      params?: { page?: number; per_page?: number },
    ) {
      return client.get<PaginatedResponse<ChatifyMessage>>(
        `/conversations/${conversationId}/messages/search`,
        { params: { q, ...params } },
      )
    },

    fetchLinkPreview(url: string) {
      return client.get<SingleResponse<LinkPreview>>('/link-preview', { params: { url } })
    },
  }
}

export type ChatifyApi = ReturnType<typeof createChatifyApi>
