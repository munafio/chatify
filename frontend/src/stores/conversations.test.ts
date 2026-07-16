import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useConversationsStore } from './conversations'
import type { ChatifyConversation } from '../types'

function makeConversation(id: string, updatedAt: string, unread = 0): ChatifyConversation {
  return {
    type: 'conversation',
    id,
    attributes: {
      conversation_type: 'direct',
      name: null,
      unread_count: unread,
      created_at: updatedAt,
      updated_at: updatedAt,
    },
    relationships: {
      participants: [],
      last_message: null,
      other_user: null,
    },
  }
}

describe('useConversationsStore inbox updates', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('reorders conversations when inbox updates arrive', () => {
    const store = useConversationsStore()
    store.items = [
      makeConversation('c1', '2026-01-01T10:00:00Z'),
      makeConversation('c2', '2026-01-02T10:00:00Z'),
    ]

    store.handleInboxUpdate(
      {
        ...makeConversation('c1', '2026-01-03T10:00:00Z', 2),
        relationships: {
          participants: [],
          last_message: null,
          other_user: null,
        },
      },
      1,
    )

    expect(store.items[0]?.id).toBe('c1')
    expect(store.items[0]?.attributes.unread_count).toBe(2)
  })

  it('clears unread for the active conversation', () => {
    const store = useConversationsStore()
    store.activeId = 'c1'
    store.handleInboxUpdate(makeConversation('c1', '2026-01-03T10:00:00Z', 4), 1)

    expect(store.items[0]?.attributes.unread_count).toBe(0)
  })
})
