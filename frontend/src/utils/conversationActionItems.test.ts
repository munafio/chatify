import { describe, expect, it } from 'vitest'
import { buildConversationActionItems } from './conversationActionItems'
import type { ChatifyConversation } from '../types'

const baseConversation: ChatifyConversation = {
  type: 'conversation',
  id: 'conv-1',
  attributes: {
    conversation_type: 'direct',
    name: null,
    unread_count: 2,
    is_pinned: false,
    created_at: '2026-01-01T10:00:00Z',
    updated_at: '2026-01-01T10:00:00Z',
  },
  relationships: {
    participants: [],
    last_message: null,
    other_user: {
      type: 'user',
      id: 2,
      attributes: {
        name: 'Alice',
        avatar: 'https://example.test/avatar.png',
      },
    },
  },
}

describe('buildConversationActionItems', () => {
  it('includes pin and mark as read for unread conversations', () => {
    const items = buildConversationActionItems({
      conversation: baseConversation,
      isFavorite: false,
      isBlocked: false,
    })

    expect(items.some((item) => item.id === 'pin')).toBe(true)
    expect(items.some((item) => item.id === 'markRead')).toBe(true)
    expect(items.some((item) => item.id === 'block')).toBe(true)
    expect(items.some((item) => item.id === 'hideConversation')).toBe(true)
  })

  it('includes delete group for owners', () => {
    const items = buildConversationActionItems({
      conversation: {
        ...baseConversation,
        attributes: {
          ...baseConversation.attributes,
          conversation_type: 'group',
          name: 'Team',
          is_owner: true,
        },
      },
      isFavorite: false,
      isBlocked: false,
    })

    expect(items.some((item) => item.id === 'deleteGroup')).toBe(true)
  })

  it('hides pin, contact info, and favorite when messaging is blocked', () => {
    const items = buildConversationActionItems({
      conversation: baseConversation,
      isFavorite: true,
      isBlocked: true,
      isMessagingBlocked: true,
    })

    expect(items.some((item) => item.id === 'pin')).toBe(false)
    expect(items.some((item) => item.id === 'unpin')).toBe(false)
    expect(items.some((item) => item.id === 'contactInfo')).toBe(false)
    expect(items.some((item) => item.id === 'favorite')).toBe(false)
    expect(items.some((item) => item.id === 'unfavorite')).toBe(false)
    expect(items.some((item) => item.id === 'unblock')).toBe(true)
    expect(items.some((item) => item.id === 'hideConversation')).toBe(true)
  })

  it('only exposes clear chat for saved conversations', () => {
    const items = buildConversationActionItems({
      conversation: {
        ...baseConversation,
        attributes: {
          ...baseConversation.attributes,
          conversation_type: 'saved',
          is_saved: true,
          saved_title: 'Saved Messages',
          name: 'Saved Messages',
        },
        relationships: {
          ...baseConversation.relationships,
          other_user: null,
        },
      },
      isFavorite: false,
      isBlocked: false,
    })

    expect(items).toEqual([
      expect.objectContaining({ id: 'clearSavedMessages', label: 'Clear chat' }),
    ])
  })
})
