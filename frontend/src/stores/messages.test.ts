import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useMessagesStore } from '../stores/messages'
import type { ChatifyMessage } from '../types'

function makeMessage(id: string, body: string, createdAt: string): ChatifyMessage {
  return {
    type: 'message',
    id,
    attributes: {
      conversation_id: 'conv-1',
      body,
      attachment: null,
      read: false,
      created_at: createdAt,
      updated_at: createdAt,
    },
    relationships: {
      sender: {
        data: { type: 'user', id: 1 },
      },
    },
  }
}

describe('useMessagesStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('upserts and sorts messages chronologically', () => {
    const store = useMessagesStore()

    store.upsertMessage('conv-1', makeMessage('m2', 'Second', '2026-01-02T10:00:00Z'))
    store.upsertMessage('conv-1', makeMessage('m1', 'First', '2026-01-01T10:00:00Z'))

    expect(store.byConversation['conv-1'].items.map((item) => item.id)).toEqual(['m1', 'm2'])
  })

  it('removes deleted messages', () => {
    const store = useMessagesStore()
    store.upsertMessage('conv-1', makeMessage('m1', 'Hello', '2026-01-01T10:00:00Z'))

    store.handleMessageDeleted({ id: 'm1', conversation_id: 'conv-1' })

    expect(store.byConversation['conv-1'].items).toHaveLength(0)
  })
})
