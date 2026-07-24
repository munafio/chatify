import { describe, expect, it } from 'vitest'
import { formatMessageDate } from './format'
import { buildConversationActionItems } from './conversationActionItems'
import { formatSystemMessage } from './systemMessage'
import type { ChatifyConversation, ChatifyMessage } from '../types'

const conversation: ChatifyConversation = {
  type: 'conversation',
  id: 'conv-1',
  attributes: {
    conversation_type: 'direct',
    name: null,
    unread_count: 1,
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
      attributes: { name: 'Alice', avatar: 'https://example.test/a.png' },
    },
  },
}

describe('i18n format helpers', () => {
  it('returns translated today label', () => {
    const today = new Date()
    today.setHours(12, 0, 0, 0)

    expect(formatMessageDate(today.toISOString())).toBe('Today')
  })

  it('builds translated conversation actions', () => {
    const items = buildConversationActionItems({
      conversation,
      isFavorite: false,
      isBlocked: false,
    })

    expect(items.some((item) => item.label === 'Mark as read')).toBe(true)
    expect(items.some((item) => item.label === 'Block')).toBe(true)
  })
})

describe('i18n system messages', () => {
  it('formats participant added event in English', () => {
    const message: ChatifyMessage = {
      type: 'message',
      id: 'msg-1',
      attributes: {
        conversation_id: 'conv-1',
        body: '',
        attachment: null,
        read: true,
        created_at: '2026-01-01T12:00:00Z',
        updated_at: '2026-01-01T12:00:00Z',
        kind: 'system',
        system_event: {
          event: 'participant_added',
          actor_user_id: 1,
          target_user_ids: [2],
        },
      },
      relationships: {
        sender: { data: { type: 'user', id: 1 } },
      },
    }

    expect(formatSystemMessage(message, (id) => (id === '2' ? 'Alice' : 'Bob'), 99)).toBe('Bob added Alice')
  })
})
