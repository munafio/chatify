import { getBootLocale } from '../i18n/bootLocale'
import { chatifyT } from '../i18n/nonComponent'
import type { ChatifyConversation, ChatifyUser } from '../types'
import { isParticipantRecord, participantUser } from './group'
import { displayUserAvatar, displayUserName } from './userDisplay'

export function formatRelativeTime(iso: string | null | undefined): string {
  if (!iso) {
    return ''
  }

  const date = new Date(iso)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const locale = getBootLocale()

  if (diffDays === 0) {
    return date.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' })
  }

  if (diffDays === 1) {
    return chatifyT('dates.yesterday')
  }

  if (diffDays < 7) {
    return date.toLocaleDateString(locale, { weekday: 'short' })
  }

  return date.toLocaleDateString(locale, { month: 'short', day: 'numeric' })
}

export function formatDurationMs(ms: number): string {
  const totalSeconds = Math.max(0, Math.floor(ms / 1000))
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${minutes}:${String(seconds).padStart(2, '0')}`
}

export function formatMessageTime(iso: string | null | undefined): string {
  if (!iso) {
    return ''
  }

  return new Date(iso).toLocaleTimeString(getBootLocale(), { hour: '2-digit', minute: '2-digit' })
}

export function formatMessageDate(iso: string | null | undefined): string {
  if (!iso) {
    return ''
  }

  const date = new Date(iso)
  const now = new Date()
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const startOfDate = new Date(date.getFullYear(), date.getMonth(), date.getDate())
  const diffDays = Math.round((startOfToday.getTime() - startOfDate.getTime()) / (1000 * 60 * 60 * 24))
  const locale = getBootLocale()

  if (diffDays === 0) {
    return chatifyT('dates.today')
  }

  if (diffDays === 1) {
    return chatifyT('dates.yesterday')
  }

  return date.toLocaleDateString(locale, { month: 'numeric', day: 'numeric', year: 'numeric' })
}

export function truncate(text: string, max = 48): string {
  if (text.length <= max) {
    return text
  }

  return `${text.slice(0, max - 1)}…`
}

export function conversationDisplayName(
  conversation: {
    attributes: {
      conversation_type: string
      name: string | null
      saved_title?: string | null
      is_saved?: boolean
    }
    relationships: { other_user: ChatifyUser | null }
  },
  fallbackSavedTitle = chatifyT('ui.saved_messages'),
): string {
  if (conversation.attributes.conversation_type === 'saved' || conversation.attributes.is_saved) {
    return conversation.attributes.saved_title ?? conversation.attributes.name ?? fallbackSavedTitle
  }

  if (conversation.attributes.conversation_type === 'group') {
    return conversation.attributes.name ?? chatifyT('ui.format.group_label')
  }

  return displayUserName(conversation.relationships.other_user)
}

export function isSavedConversation(conversation: Pick<ChatifyConversation, 'attributes'>): boolean {
  return conversation.attributes.conversation_type === 'saved' || Boolean(conversation.attributes.is_saved)
}

export function conversationAvatar(
  conversation: ChatifyConversation,
  defaultAvatarUrl = '',
): string | null {
  if (isSavedConversation(conversation)) {
    return null
  }

  if (conversation.attributes.conversation_type === 'direct') {
    const otherUser = conversation.relationships.other_user
    if (!otherUser) {
      return null
    }

    return displayUserAvatar(otherUser, defaultAvatarUrl) || null
  }

  if (conversation.attributes.avatar_url) {
    return conversation.attributes.avatar_url
  }

  const first = conversation.relationships.participants[0]
  if (!first) {
    return null
  }

  if (isParticipantRecord(first)) {
    return participantUser(first)?.attributes.avatar ?? null
  }

  return first.attributes.avatar ?? null
}
