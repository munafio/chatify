import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useConfirmStore } from './confirm'

describe('useConfirmStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('opens dialog and resolves true on confirm', async () => {
    const store = useConfirmStore()
    const promise = store.confirm({ title: 'Test?' })

    expect(store.options).not.toBeNull()
    expect(store.options?.title).toBe('Test?')

    store.answer(true)
    await expect(promise).resolves.toBe(true)
    expect(store.options).toBeNull()
  })

  it('resolves false on cancel', async () => {
    const store = useConfirmStore()
    const promise = store.confirm({ title: 'Cancel me' })

    store.answer(false)
    await expect(promise).resolves.toBe(false)
    expect(store.options).toBeNull()
  })

  it('only shows one dialog at a time', async () => {
    const store = useConfirmStore()
    const first = store.confirm({ title: 'First' })
    const second = store.confirm({ title: 'Second' })

    await expect(first).resolves.toBe(false)
    expect(store.options?.title).toBe('Second')

    store.answer(true)
    await expect(second).resolves.toBe(true)
    expect(store.options).toBeNull()
  })

  it('applies default labels and variant', () => {
    const store = useConfirmStore()

    store.confirm({ title: 'Defaults' })
    expect(store.options).toMatchObject({
      title: 'Defaults',
      confirmLabel: 'Confirm',
      cancelLabel: 'Cancel',
      variant: 'default',
    })

    store.answer(false)
  })
})
