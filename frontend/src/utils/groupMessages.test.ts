import { describe, expect, it } from 'vitest'
import type { ChatifyMessage } from '../types'
import { groupMessagesByDate } from './groupMessageClusters'

function makeMessage(id: string, createdAt: string): ChatifyMessage {
  return {
    type: 'message',
    id,
    attributes: {
      conversation_id: 'conv-1',
      body: 'Hello',
      attachment: null,
      read: false,
      created_at: createdAt,
      updated_at: createdAt,
    },
    relationships: {
      sender: { data: { type: 'user', id: 1 } },
    },
  }
}

describe('groupMessagesByDate', () => {
  it('inserts date separators when the day changes', () => {
    const items = groupMessagesByDate([
      makeMessage('m1', '2026-01-01T10:00:00Z'),
      makeMessage('m2', '2026-01-02T10:00:00Z'),
    ])

    expect(items.filter((item) => item.kind === 'date')).toHaveLength(2)
    expect(items.filter((item) => item.kind === 'message')).toHaveLength(2)
  })

  it('does not duplicate separators for same-day messages', () => {
    const items = groupMessagesByDate([
      makeMessage('m1', '2026-01-01T09:00:00Z'),
      makeMessage('m2', '2026-01-01T10:00:00Z'),
    ])

    expect(items.filter((item) => item.kind === 'date')).toHaveLength(1)
  })
})
