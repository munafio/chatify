import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import ContextMenu from './ContextMenu.vue'

describe('ContextMenu', () => {
  beforeEach(() => {
    document.body.innerHTML = '<div id="chatify-app"></div>'
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('emits select before close so handlers can read menu state', async () => {
    const order: string[] = []

    mount(ContextMenu, {
      props: {
        open: true,
        x: 10,
        y: 10,
        items: [{ id: 'pin', label: 'Pin' }],
        onSelect: (id: string) => {
          order.push(`select:${id}`)
        },
        onClose: () => {
          order.push('close')
        },
      },
      attachTo: document.body,
    })

    const button = document.querySelector('#chatify-app button[role="menuitem"]')
    expect(button).toBeTruthy()
    button!.dispatchEvent(new MouseEvent('click', { bubbles: true }))

    expect(order).toEqual(['select:pin', 'close'])
  })
})
