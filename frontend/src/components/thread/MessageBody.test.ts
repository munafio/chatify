import { flushPromises, mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import MessageBody from './MessageBody.vue'
import { useConfigStore } from '../../stores/config'
import { clearLinkPreviewCache, setLinkPreview } from '../../utils/linkPreviewCache'

describe('MessageBody link preview', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    clearLinkPreviewCache()
  })

  it('shows skeleton while loading and card after fetch resolves', async () => {
    const fetchLinkPreview = vi.fn().mockResolvedValue({
      data: {
        data: {
          url: 'https://example.com/article',
          title: 'Example article',
          description: 'Preview description',
          image: 'https://example.com/image.jpg',
          site_name: 'Example',
        },
      },
    })

    const configStore = useConfigStore()
    configStore.api = { fetchLinkPreview } as never

    const wrapper = mount(MessageBody, {
      props: {
        body: 'Check this out https://example.com/article',
      },
    })

    await nextTick()

    expect(wrapper.find('.chatify-link-preview-card-skeleton').exists()).toBe(true)
    expect(wrapper.find('.chatify-link-preview-card:not(.chatify-link-preview-card-skeleton)').exists()).toBe(false)

    await flushPromises()

    expect(fetchLinkPreview).toHaveBeenCalledWith('https://example.com/article')
    expect(wrapper.find('.chatify-link-preview-card-skeleton').exists()).toBe(false)
    expect(wrapper.text()).toContain('Example article')
  })

  it('renders cached preview immediately without skeleton', async () => {
    const fetchLinkPreview = vi.fn()

    setLinkPreview('https://example.com/cached', {
      url: 'https://example.com/cached',
      title: 'Cached article',
    })

    const configStore = useConfigStore()
    configStore.api = { fetchLinkPreview } as never

    const wrapper = mount(MessageBody, {
      props: { body: 'Again https://example.com/cached please' },
    })

    await nextTick()

    expect(wrapper.find('.chatify-link-preview-card-skeleton').exists()).toBe(false)
    expect(wrapper.text()).toContain('Cached article')
    expect(fetchLinkPreview).not.toHaveBeenCalled()
  })
})
