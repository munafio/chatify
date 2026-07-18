import { describe, expect, it } from 'vitest'
import { shouldPlayIncomingSound } from './chatSounds'

describe('shouldPlayIncomingSound', () => {
  const base = {
    conversationId: '10',
    senderId: 2,
    currentUserId: 1,
    activeConversationId: '10',
    isSenderBlocked: false,
  }

  it('does not play for the current user messages', () => {
    expect(shouldPlayIncomingSound({ ...base, senderId: 1, currentUserId: 1 })).toBe(false)
  })

  it('does not play for blocked senders', () => {
    expect(shouldPlayIncomingSound({ ...base, isSenderBlocked: true })).toBe(false)
  })

  it('does not play when viewing the same chat with a visible tab', () => {
    Object.defineProperty(document, 'visibilityState', {
      configurable: true,
      value: 'visible',
    })

    expect(shouldPlayIncomingSound(base)).toBe(false)
  })

  it('plays when viewing the same chat but the tab is hidden', () => {
    Object.defineProperty(document, 'visibilityState', {
      configurable: true,
      value: 'hidden',
    })

    expect(shouldPlayIncomingSound(base)).toBe(true)
  })

  it('plays for another conversation even when the tab is visible', () => {
    Object.defineProperty(document, 'visibilityState', {
      configurable: true,
      value: 'visible',
    })

    expect(shouldPlayIncomingSound({ ...base, activeConversationId: '99' })).toBe(true)
  })

  it('plays when no conversation is open', () => {
    Object.defineProperty(document, 'visibilityState', {
      configurable: true,
      value: 'visible',
    })

    expect(shouldPlayIncomingSound({ ...base, activeConversationId: null })).toBe(true)
  })
})
