import { describe, expect, it } from 'vitest'
import {
  conversationTypeIconLabelKey,
  isConversationTypeIconKind,
  resolveConversationTypeIconKind,
} from './conversationTypeIcon'
import type { ChatifyConversation } from '../types'

const groupConversation = {
  attributes: { conversation_type: 'group' as const },
} as ChatifyConversation

const savedConversation = {
  attributes: { conversation_type: 'saved' as const, is_saved: true },
} as ChatifyConversation

const directConversation = {
  attributes: { conversation_type: 'direct' as const },
} as ChatifyConversation

describe('conversationTypeIcon', () => {
  it('resolves icon kinds from conversation type', () => {
    expect(resolveConversationTypeIconKind(groupConversation)).toBe('group')
    expect(resolveConversationTypeIconKind(savedConversation)).toBe('saved')
    expect(resolveConversationTypeIconKind(directConversation)).toBeNull()
  })

  it('maps kinds to i18n label keys', () => {
    expect(conversationTypeIconLabelKey('group')).toBe('ui.conversation_type.group')
    expect(conversationTypeIconLabelKey('saved')).toBe('ui.conversation_type.saved')
  })

  it('validates known icon kinds', () => {
    expect(isConversationTypeIconKind('group')).toBe(true)
    expect(isConversationTypeIconKind('saved')).toBe(true)
    expect(isConversationTypeIconKind('direct')).toBe(false)
  })
})
