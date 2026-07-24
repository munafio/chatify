import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import ThreadHeader from './ThreadHeader.vue'
import { useConfigStore } from '../../stores/config'
import { useConnectionStore } from '../../stores/connection'
import { useUiStore } from '../../stores/ui'
import type { ChatifyConversation } from '../../types'

const directConversation: ChatifyConversation = {
  type: 'conversation',
  id: 'conv-direct',
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

function makeConversation(type: 'direct' | 'group' | 'saved'): ChatifyConversation {
  if (type === 'direct') {
    return directConversation
  }

  if (type === 'group') {
    return {
      ...directConversation,
      id: 'conv-group',
      attributes: {
        ...directConversation.attributes,
        conversation_type: 'group',
        name: 'Team Chat',
        participant_count: 3,
      },
      relationships: {
        ...directConversation.relationships,
        other_user: null,
      },
    }
  }

  return {
    ...directConversation,
    id: 'conv-saved',
    attributes: {
      ...directConversation.attributes,
      conversation_type: 'saved',
    },
    relationships: {
      ...directConversation.relationships,
      other_user: null,
    },
  }
}

describe('ThreadHeader message search', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useConfigStore()
    useConnectionStore()
  })

  it.each(['direct', 'group', 'saved'] as const)(
    'shows search button for %s conversations',
    (conversationType) => {
      const wrapper = mount(ThreadHeader, {
        props: {
          conversation: makeConversation(conversationType),
        },
      })

      expect(wrapper.find('[aria-label="Search conversation"]').exists()).toBe(true)
    },
  )

  it('opens message search when search button is clicked', async () => {
    const uiStore = useUiStore()
    const openMessageSearch = vi.spyOn(uiStore, 'openMessageSearch')

    const wrapper = mount(ThreadHeader, {
      props: {
        conversation: directConversation,
      },
    })

    await wrapper.find('[aria-label="Search conversation"]').trigger('click')

    expect(openMessageSearch).toHaveBeenCalledOnce()
  })
})
