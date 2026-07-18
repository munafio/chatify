import { describe, expect, it } from 'vitest'
import type { ChatifyMessage } from '../types'
import { formatSystemMessage, isSystemMessage } from './systemMessage'

function makeSystemMessage(systemEvent: ChatifyMessage['attributes']['system_event'], body = ''): ChatifyMessage {
  return {
    type: 'message',
    id: 'sys-1',
    attributes: {
      conversation_id: 'conv-1',
      kind: 'system',
      system_event: systemEvent,
      body,
      attachment: null,
      read: true,
      created_at: '2026-01-01T10:00:00Z',
      updated_at: '2026-01-01T10:00:00Z',
    },
    relationships: {
      sender: {
        data: {
          type: 'user',
          id: 1,
        },
      },
    },
  }
}

describe('systemMessage utils', () => {
  it('detects system messages', () => {
    expect(isSystemMessage(makeSystemMessage(null))).toBe(true)
  })

  it('formats added messages with You labels', () => {
    const message = makeSystemMessage({
      event: 'participant_added',
      actor_user_id: 1,
      target_user_ids: [2],
    })

    const label = formatSystemMessage(
      message,
      (id) => (id === '2' ? 'Bob' : 'Alice'),
      1,
    )

    expect(label).toBe('You added Bob')
  })

  it('formats removed messages', () => {
    const message = makeSystemMessage({
      event: 'participant_removed',
      actor_user_id: 1,
      target_user_ids: [2],
    })

    const label = formatSystemMessage(
      message,
      (id) => (id === '1' ? 'Alice' : 'Bob'),
    )

    expect(label).toBe('Alice removed Bob')
  })
})
