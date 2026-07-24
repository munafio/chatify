export interface ChatifyUser {
  type: 'user'
  id: number | string
  attributes: {
    name: string
    avatar: string
    email?: string | null
    is_online?: boolean
    is_blocked_by_me?: boolean
    is_blocked_by_them?: boolean
    is_identity_hidden?: boolean
  }
}

export type GroupMemberRole = 'owner' | 'admin' | 'moderator' | 'member'

export type GroupPermissionKey = 'edit_info' | 'add_members' | 'remove_members' | 'manage_admins'

export interface GroupMembership {
  role: GroupMemberRole
  permissions: Partial<Record<GroupPermissionKey, boolean>> | null
  is_full_admin: boolean
}

export interface ChatifyParticipant {
  type: 'participant'
  id: number | string
  attributes: {
    role: GroupMemberRole
    permissions: Partial<Record<GroupPermissionKey, boolean>> | null
    is_full_admin: boolean
    is_you: boolean
  }
  relationships: {
    user: ChatifyUser | null
  }
}

export interface GroupCreatedBy {
  id: number | string
  name: string
}

export interface MessageAttachment {
  filename: string
  original_name: string | null
  type: 'image' | 'file' | string
  url: string
}

export interface SharedAttachment {
  type: 'attachment'
  attributes: {
    kind: 'media' | 'doc' | 'link' | string
    filename: string | null
    url: string | null
    original_name: string | null
    mime: string | null
    snippet: string | null
    message_id: string | null
    created_at: string | null
  }
}

export interface MessageReplyPreview {
  id: string
  body: string | null
  sender_name: string
}

export interface LinkPreview {
  url: string
  title?: string | null
  description?: string | null
  image?: string | null
  site_name?: string | null
}

export interface ChatifyMessage {
  type: 'message'
  id: string
  attributes: {
    conversation_id: string
    kind?: 'user' | 'system'
    system_event?: {
      event: 'participant_added' | 'participant_removed' | 'participant_left'
      actor_user_id: number | string
      target_user_ids: Array<number | string>
    } | null
    body: string | null
    attachment: MessageAttachment | null
    attachments?: MessageAttachment[]
    read: boolean
    edited_at?: string | null
    reply_to?: MessageReplyPreview | null
    forwarded_from?: MessageReplyPreview | null
    local_status?: 'sending' | 'failed' | null
    upload_progress?: number | null
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
    conversation_type: 'direct' | 'group' | 'saved'
    name: string | null
    description?: string | null
    avatar_url?: string | null
    is_saved?: boolean
    saved_title?: string | null
    unread_count: number
    is_pinned?: boolean
    pin_order?: number | null
    participant_count?: number
    is_owner?: boolean
    my_membership?: GroupMembership | null
    created_by?: GroupCreatedBy | null
    created_at: string | null
    updated_at: string | null
  }
  relationships: {
    participants: ChatifyUser[] | ChatifyParticipant[]
    participants_preview?: ChatifyParticipant[] | null
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
    active_status?: boolean
  }
}

export interface BootPreferences {
  dark_mode: boolean
  theme_preferences: Record<string, unknown> | null
  chat_background_url: string | null
  show_online_status?: boolean
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

export interface WallpaperPatternBoot {
  id: string
  name: string
  url: string
}

export interface AppearanceFeatures {
  giphy: boolean
  colors: boolean
  themes: boolean
  fonts: boolean
  wallpaper: boolean
}

export interface SoundEventConfig {
  enabled: boolean
  url: string | null
}

export interface SoundsConfig {
  enabled: boolean
  incomingMessage: SoundEventConfig
  outgoingMessage: SoundEventConfig
  typing: SoundEventConfig
}

export interface SavedMessagesConfig {
  enabled: boolean
  title: string
}

export interface BootConfig {
  user: ChatifyUser
  apiBase: string
  broadcastAuthUrl: string
  csrfToken: string
  conversationId: string | null
  webBase: string
  locale: string
  fallbackLocale: string
  dir: 'ltr' | 'rtl'
  translations: Record<string, unknown>
  fallbackTranslations?: Record<string, unknown> | null
  appName?: string
  debug: boolean
  groupsEnabled: boolean
  features?: AppearanceFeatures
  colors: string[]
  themes?: string[]
  fonts?: string[]
  wallpaperPatterns?: WallpaperPatternBoot[]
  preferences?: BootPreferences
  attachments: AttachmentsConfig
  giphy?: {
    enabled: boolean
    apiKey: string | null
  }
  broadcast: BroadcastConfig
  messaging_blocked_user_ids?: Array<number | string>
  default_avatar_url?: string
  sounds?: SoundsConfig
  savedMessages?: SavedMessagesConfig
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
    total_all?: number
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

export type GroupInfoView = 'main' | 'members' | 'media'

export type GroupMembersMode = 'browse' | 'add'

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
  change_type?: 'participant_added' | 'participant_removed' | 'participant_left' | string | null
  actor_user_id?: number | string | null
  target_user_ids?: Array<number | string>
  participant_count: number
  participants?: ChatifyUser[]
  participants_preview?: ChatifyParticipant[]
}

export interface GroupMembershipRevokedPayload {
  conversation_id: string
  user_id: number | string
  reason: 'removed' | 'left'
}

export interface UserTypingPayload {
  conversation_id: string
  user_id: number | string
  is_typing: boolean
}

export interface UserPresenceChangedPayload {
  user_id: number | string
  is_online: boolean
}

export interface UserBlockChangedPayload {
  blocker_id: number | string
  blocked_user_id: number | string
  blocked: boolean
  messaging_blocked_user_ids: Array<number | string>
}

export interface MessageClusterEntry {
  message: ChatifyMessage
  showSenderName?: boolean
  clusterSpacing?: 'tight' | 'normal'
}

export type MessageThreadEntry =
  | {
      kind: 'message'
      key: string
      message: ChatifyMessage
      showAvatar?: boolean
      showSenderName?: boolean
      clusterSpacing?: 'tight' | 'normal'
    }
  | {
      kind: 'cluster'
      key: string
      senderId: string
      entries: MessageClusterEntry[]
    }

export type MessageListItem =
  | { kind: 'date'; key: string; label: string }
  | MessageThreadEntry
  | {
      kind: 'day'
      key: string
      label: string
      items: MessageThreadEntry[]
    }
