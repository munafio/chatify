import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useConversationsStore } from './conversations'
import { useConfigStore } from './config'
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

  it('keeps newer local inbox state when fetchAll returns stale data', async () => {
    const store = useConversationsStore()
    const configStore = useConfigStore()

    store.items = [
      {
        ...makeConversation('c1', '2026-01-01T10:00:00Z'),
        relationships: {
          participants: [],
          other_user: null,
          last_message: {
            type: 'message',
            id: 'm-new',
            attributes: {
              conversation_id: 'c1',
              body: 'Fresh realtime message',
              attachment: null,
              read: false,
              created_at: '2026-01-05T10:00:00Z',
              updated_at: '2026-01-05T10:00:00Z',
            },
            relationships: {
              sender: {
                data: {
                  type: 'user',
                  id: 2,
                },
              },
            },
          },
        },
      },
    ]

    configStore.api = {
      getConversations: async () => ({
        data: {
          data: [
            {
              ...makeConversation('c1', '2026-01-01T10:00:00Z', 1),
              relationships: {
                participants: [],
                other_user: null,
                last_message: {
                  type: 'message',
                  id: 'm-old',
                  attributes: {
                    conversation_id: 'c1',
                    body: 'Stale fetched message',
                    attachment: null,
                    read: false,
                    created_at: '2026-01-01T10:00:00Z',
                    updated_at: '2026-01-01T10:00:00Z',
                  },
                  relationships: {
                    sender: {
                      data: {
                        type: 'user',
                        id: 2,
                      },
                    },
                  },
                },
              },
            },
          ],
        },
      }),
    } as never

    await store.fetchAll()

    expect(store.items[0]?.relationships.last_message?.id).toBe('m-new')
    expect(store.items[0]?.relationships.last_message?.attributes.body).toBe('Fresh realtime message')
    expect(store.items[0]?.attributes.unread_count).toBe(1)
  })

  it('sets loadFailed when fetchAll fails without exposing raw error text', async () => {
    const store = useConversationsStore()
    const configStore = useConfigStore()

    configStore.api = {
      getConversations: async () => {
        throw new Error('Request failed with status code 401')
      },
    } as never

    await store.fetchAll()

    expect(store.loadFailed).toBe(true)
    expect(store.items).toEqual([])
  })
})
