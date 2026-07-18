import type { ChatifyConversation } from '../types'
import { isParticipantRecord, participantUser } from './group'

export function formatRelativeTime(iso: string | null | undefined): string {
  if (!iso) {
    return ''
  }

  const date = new Date(iso)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

  if (diffDays === 0) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  }

  if (diffDays === 1) {
    return 'Yesterday'
  }

  if (diffDays < 7) {
    return date.toLocaleDateString([], { weekday: 'short' })
  }

  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
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

  return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
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

  if (diffDays === 0) {
    return 'Today'
  }

  if (diffDays === 1) {
    return 'Yesterday'
  }

  return date.toLocaleDateString([], { month: 'numeric', day: 'numeric', year: 'numeric' })
}

export function truncate(text: string, max = 48): string {
  if (text.length <= max) {
    return text
  }

  return `${text.slice(0, max - 1)}…`
}

export function conversationDisplayName(
  conversation: { attributes: { conversation_type: string; name: string | null }; relationships: { other_user: { attributes: { name: string } } | null } },
): string {
  if (conversation.attributes.conversation_type === 'group') {
    return conversation.attributes.name ?? 'Group'
  }

  return conversation.relationships.other_user?.attributes.name ?? 'Unknown'
}

export function conversationAvatar(conversation: ChatifyConversation): string | null {
  if (conversation.attributes.conversation_type === 'direct') {
    return conversation.relationships.other_user?.attributes.avatar ?? null
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
