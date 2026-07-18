import { describe, expect, it } from 'vitest'
import { sortConversations } from './sortConversations'
import type { ChatifyConversation } from '../types'

function makeConversation(
  id: string,
  options: {
    pinned?: boolean
    pinOrder?: number
    updatedAt?: string
  } = {},
): ChatifyConversation {
  return {
    type: 'conversation',
    id,
    attributes: {
      conversation_type: 'direct',
      name: null,
      unread_count: 0,
      is_pinned: options.pinned ?? false,
      pin_order: options.pinOrder ?? null,
      created_at: options.updatedAt ?? '2026-01-01T10:00:00Z',
      updated_at: options.updatedAt ?? '2026-01-01T10:00:00Z',
    },
    relationships: {
      participants: [],
      last_message: options.updatedAt
        ? {
            type: 'message',
            id: `msg-${id}`,
            attributes: {
              conversation_id: id,
              body: 'hello',
              attachment: null,
              read: true,
              created_at: options.updatedAt,
              updated_at: options.updatedAt,
            },
            relationships: {
              sender: { data: { type: 'user', id: 1 } },
            },
          }
        : null,
      other_user: null,
    },
  }
}

describe('sortConversations', () => {
  it('sorts pinned conversations before recent ones', () => {
    const sorted = sortConversations([
      makeConversation('recent', { updatedAt: '2026-01-03T10:00:00Z' }),
      makeConversation('pinned', { pinned: true, pinOrder: 0, updatedAt: '2026-01-01T10:00:00Z' }),
    ])

    expect(sorted.map((item) => item.id)).toEqual(['pinned', 'recent'])
  })

  it('orders pinned conversations by pin_order', () => {
    const sorted = sortConversations([
      makeConversation('b', { pinned: true, pinOrder: 1 }),
      makeConversation('a', { pinned: true, pinOrder: 0 }),
    ])

    expect(sorted.map((item) => item.id)).toEqual(['a', 'b'])
  })

  it('places saved conversations before pinned and recent chats', () => {
    const saved: ChatifyConversation = {
      ...makeConversation('saved'),
      attributes: {
        ...makeConversation('saved').attributes,
        conversation_type: 'saved',
        is_saved: true,
        saved_title: 'Saved Messages',
        name: 'Saved Messages',
      },
    }

    const sorted = sortConversations([
      makeConversation('recent', { updatedAt: '2026-01-03T10:00:00Z' }),
      saved,
      makeConversation('pinned', { pinned: true, pinOrder: 0, updatedAt: '2026-01-01T10:00:00Z' }),
    ])

    expect(sorted.map((item) => item.id)).toEqual(['saved', 'pinned', 'recent'])
  })
})
