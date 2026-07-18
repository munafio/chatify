import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { describe, expect, it } from 'vitest'
import GroupEditFields from './GroupEditFields.vue'

describe('GroupEditFields', () => {
  it('cancels name edits on Escape and restores the original value', async () => {
    const wrapper = mount(GroupEditFields, {
      props: {
        name: 'Original name',
        description: 'Original description',
        editable: true,
      },
    })

    await wrapper.get('[aria-label="Edit group name"]').trigger('click')
    await nextTick()

    const input = wrapper.get('input')
    await input.setValue('Changed name')
    await input.trigger('keydown', { key: 'Escape' })
    await nextTick()

    expect(wrapper.text()).toContain('Original name')
    expect(wrapper.emitted('saveName')).toBeUndefined()
  })
})
