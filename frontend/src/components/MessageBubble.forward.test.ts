import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import MessageBubble from '../components/thread/MessageBubble.vue'
import { useConfigStore } from '../stores/config'
import type { ChatifyMessage } from '../types'

const baseMessage: ChatifyMessage = {
  type: 'message',
  id: 'msg-1',
  attributes: {
    conversation_id: 'conv-1',
    body: 'Forwarded body',
    attachment: null,
    read: true,
    created_at: '2026-01-01T12:00:00Z',
    updated_at: '2026-01-01T12:00:00Z',
  },
  relationships: {
    sender: {
      data: { type: 'user', id: 2 },
    },
  },
}

describe('MessageBubble forwarded label', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useConfigStore()
  })

  it('renders a forwarded label when forwarded_from is present', () => {
    const wrapper = mount(MessageBubble, {
      props: {
        message: {
          ...baseMessage,
          attributes: {
            ...baseMessage.attributes,
            forwarded_from: {
              id: 'orig-1',
              body: 'Original',
              sender_name: 'Alice',
            },
          },
        },
        isOwn: false,
      },
    })

    expect(wrapper.text()).toContain('Forwarded')
    expect(wrapper.text()).toContain('Forwarded body')
  })
})
