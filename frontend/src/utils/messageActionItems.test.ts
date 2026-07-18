import { describe, expect, it } from 'vitest'
import { buildMessageActionItems } from './messageActionItems'

describe('buildMessageActionItems', () => {
  it('includes copy text when message body exists', () => {
    const items = buildMessageActionItems({ isOwn: false, hasCopyableText: true })
    expect(items[0]).toMatchObject({ id: 'copy', label: 'Copy text' })
    expect(items.some((item) => item.id === 'reply')).toBe(true)
    expect(items.some((item) => item.id === 'removeForAll')).toBe(false)
  })

  it('includes edit and remove for everyone on own messages', () => {
    const items = buildMessageActionItems({ isOwn: true, hasCopyableText: false })
    expect(items.some((item) => item.id === 'edit')).toBe(true)
    expect(items.some((item) => item.id === 'removeForAll')).toBe(true)
  })
})
