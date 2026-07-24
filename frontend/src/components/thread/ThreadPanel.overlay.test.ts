import { shallowMount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import ThreadPanel from './ThreadPanel.vue'
import MessageList from './MessageList.vue'
import ThreadMessageSearch from './ThreadMessageSearch.vue'
import { useConversationsStore } from '../../stores/conversations'
import { useConfigStore } from '../../stores/config'
import { useUiStore } from '../../stores/ui'
import type { ChatifyConversation } from '../../types'

const activeConversation: ChatifyConversation = {
  type: 'conversation',
  id: 'conv-1',
  attributes: {
    conversation_type: 'direct',
    name: null,
    unread_count: 0,
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
        avatar: 'https://example.test/alice.png',
      },
    },
  },
}

describe('ThreadPanel message search overlay', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useConfigStore()

    const conversationsStore = useConversationsStore()
    conversationsStore.$patch({
      activeId: activeConversation.id,
      items: [activeConversation],
    })
  })

  it('keeps MessageList mounted while search overlay is open', () => {
    const uiStore = useUiStore()
    uiStore.messageSearchOpen = true

    const wrapper = shallowMount(ThreadPanel)

    expect(wrapper.findComponent(MessageList).exists()).toBe(true)
    expect(wrapper.findComponent(ThreadMessageSearch).exists()).toBe(true)
  })
})
