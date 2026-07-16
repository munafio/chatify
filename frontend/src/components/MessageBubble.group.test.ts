import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import MessageBubble from '../components/thread/MessageBubble.vue'
import { useConfigStore } from '../stores/config'
import type { ChatifyMessage } from '../types'

const message: ChatifyMessage = {
  type: 'message',
  id: 'msg-1',
  attributes: {
    conversation_id: 'conv-1',
    body: 'Hello group',
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

describe('MessageBubble group chat', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    useConfigStore()
  })

  it('renders sender name for incoming group messages', () => {
    const wrapper = mount(MessageBubble, {
      props: {
        message,
        isOwn: false,
        isGroup: true,
        senderName: 'Alice',
      },
    })

    expect(wrapper.text()).toContain('Alice')
    expect(wrapper.text()).toContain('Hello group')
    expect(wrapper.html()).not.toContain('v-html')
  })

  it('does not render sender name for own messages', () => {
    const wrapper = mount(MessageBubble, {
      props: {
        message,
        isOwn: true,
        isGroup: true,
        senderName: 'Alice',
      },
    })

    expect(wrapper.text()).not.toContain('Alice')
  })
})
