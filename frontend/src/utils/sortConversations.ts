import type { ChatifyConversation } from '../types'

export function recentTimestamp(conversation: ChatifyConversation): string {
  return (
    conversation.relationships.last_message?.attributes.created_at
    ?? conversation.attributes.updated_at
    ?? conversation.attributes.created_at
    ?? ''
  )
}

export function conversationListKey(conversation: ChatifyConversation): string {
  const lastMessage = conversation.relationships.last_message
  return [
    conversation.id,
    conversation.attributes.unread_count,
    conversation.attributes.updated_at ?? '',
    lastMessage?.id ?? '',
    lastMessage?.attributes.created_at ?? '',
    lastMessage?.attributes.body ?? '',
  ].join(':')
}

export function sortConversations(list: ChatifyConversation[]): ChatifyConversation[] {
  const saved = list.filter((conversation) => conversation.attributes.conversation_type === 'saved')
  const rest = list.filter((conversation) => conversation.attributes.conversation_type !== 'saved')

  const pinned = rest
    .filter((conversation) => conversation.attributes.is_pinned)
    .sort(
      (a, b) =>
        (a.attributes.pin_order ?? Number.MAX_SAFE_INTEGER)
        - (b.attributes.pin_order ?? Number.MAX_SAFE_INTEGER),
    )

  const unpinned = rest
    .filter((conversation) => !conversation.attributes.is_pinned)
    .sort((a, b) => recentTimestamp(b).localeCompare(recentTimestamp(a)))

  return [...saved, ...pinned, ...unpinned]
}
