import type { ChatifyConversation } from '../types'
import { isSavedConversation } from './format'

export type ConversationTypeIconKind = 'group' | 'saved'

const ICON_KINDS = new Set<ConversationTypeIconKind>(['group', 'saved'])

export function isConversationTypeIconKind(value: string): value is ConversationTypeIconKind {
  return ICON_KINDS.has(value as ConversationTypeIconKind)
}

export function resolveConversationTypeIconKind(
  conversation: Pick<ChatifyConversation, 'attributes'>,
): ConversationTypeIconKind | null {
  if (conversation.attributes.conversation_type === 'group') {
    return 'group'
  }

  if (isSavedConversation(conversation)) {
    return 'saved'
  }

  return null
}

export function conversationTypeIconLabelKey(kind: ConversationTypeIconKind): string {
  return `ui.conversation_type.${kind}`
}
