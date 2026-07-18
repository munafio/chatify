import { describe, expect, it } from 'vitest'
import type { ChatifyMessage } from '../types'
import { filterMessagesFromBlockedSenders, isBlockedSender } from './blockMessaging'

function makeMessage(id: string, senderId: number | string, kind: 'user' | 'system' = 'user'): ChatifyMessage {
  return {
    type: 'message',
    id,
    attributes: {
      conversation_id: 'conv-1',
      kind,
      body: 'hello',
      attachment: null,
      read: true,
      created_at: '2026-01-01T12:00:00Z',
      updated_at: '2026-01-01T12:00:00Z',
    },
    relationships: {
      sender: {
        data: {
          type: 'user',
          id: senderId,
        },
      },
    },
  }
}

describe('blockMessaging', () => {
  it('detects blocked senders', () => {
    const blocked = new Set(['2'])

    expect(isBlockedSender(2, (id) => blocked.has(String(id)))).toBe(true)
    expect(isBlockedSender(3, (id) => blocked.has(String(id)))).toBe(false)
  })

  it('filters blocked senders but keeps own and system messages', () => {
    const blocked = new Set(['2'])
    const isMessagingBlocked = (id: number | string) => blocked.has(String(id))

    const filtered = filterMessagesFromBlockedSenders(
      [
        makeMessage('m1', 1),
        makeMessage('m2', 2),
        makeMessage('m3', 3, 'system'),
      ],
      isMessagingBlocked,
      1,
    )

    expect(filtered.map((message) => message.id)).toEqual(['m1', 'm3'])
  })
})
