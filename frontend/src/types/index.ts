export interface ChatifyUser {
  type: 'user'
  id: number | string
  attributes: {
    name: string
    avatar: string
    active_status: boolean
    email?: string | null
  }
}

export interface MessageAttachment {
  filename: string
  original_name: string | null
  type: 'image' | 'file' | string
  url: string
}

export interface MessageReplyPreview {
  id: string
  body: string | null
  sender_name: string
}

export interface ChatifyMessage {
  type: 'message'
  id: string
  attributes: {
    conversation_id: string
    body: string | null
    attachment: MessageAttachment | null
    attachments?: MessageAttachment[]
    read: boolean
    edited_at?: string | null
    reply_to?: MessageReplyPreview | null
    local_status?: 'sending' | 'failed' | null
    created_at: string | null
    updated_at: string | null
  }
  relationships: {
    sender: {
      data: {
        type: 'user'
        id: number | string
      }
    }
  }
}

export interface ChatifyConversation {
  type: 'conversation'
  id: string
  attributes: {
    conversation_type: 'direct' | 'group'
    name: string | null
    unread_count: number
    participant_count?: number
    is_owner?: boolean
    created_at: string | null
    updated_at: string | null
  }
  relationships: {
    participants: ChatifyUser[]
    last_message: ChatifyMessage | null
    other_user: ChatifyUser | null
  }
}

export interface UserSettings {
  type: 'user_settings'
  id: number | string
  attributes: {
    avatar: string
    avatar_url: string
    dark_mode: boolean
    messenger_color: string
    theme_preferences: Record<string, unknown> | null
    chat_background: string | null
    chat_background_url: string | null
    active_status: boolean
  }
}

export interface BootPreferences {
  dark_mode: boolean
  theme_preferences: Record<string, unknown> | null
  chat_background_url: string | null
}

export interface BroadcastConfig {
  driver: string
  key: string | null
  cluster: string | null
  wsHost: string | null
  wsPort: number
  forceTLS: boolean
}

export interface AttachmentsConfig {
  maxUploadSize: number
  allowedImages: string[]
  allowedFiles: string[]
}

export interface BootConfig {
  user: ChatifyUser
  apiBase: string
  broadcastAuthUrl: string
  csrfToken: string
  conversationId: string | null
  appName?: string
  debug: boolean
  groupsEnabled: boolean
  colors: string[]
  preferences?: BootPreferences
  attachments: AttachmentsConfig
  broadcast: BroadcastConfig
}

export interface PaginatedResponse<T> {
  data: T[]
  links?: {
    first?: string | null
    last?: string | null
    prev?: string | null
    next?: string | null
  }
  meta?: {
    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
  }
}

export interface SingleResponse<T> {
  data: T
}

export type ModalName =
  | 'contactInfo'
  | 'groupInfo'
  | 'createGroup'
  | 'newChat'
  | 'settings'
  | 'forwardMessage'
  | null

export interface MessageDeletedPayload {
  id: string
  conversation_id: string
}

export interface ConversationReadPayload {
  conversation_id: string
  user_id: number | string
  read_at: string
}

export interface GroupParticipantsChangedPayload {
  conversation_id: string
  participant_count: number
  participants: ChatifyUser[]
}

export interface UserTypingPayload {
  conversation_id: string
  user_id: number | string
  is_typing: boolean
}

export type MessageListItem =
  | { kind: 'date'; key: string; label: string }
  | { kind: 'message'; key: string; message: ChatifyMessage }
