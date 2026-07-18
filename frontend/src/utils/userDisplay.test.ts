import { describe, expect, it } from 'vitest'
import type { ChatifyConversation, ChatifyUser } from '../types'
import {
  displayUserAvatar,
  displayUserName,
  HIDDEN_USER_NAME,
  isIdentityHidden,
  maskUserIdentity,
  sanitizeConversationIdentity,
} from './userDisplay'

const visibleUser: ChatifyUser = {
  type: 'user',
  id: 1,
  attributes: {
    name: 'Alice',
    avatar: 'https://example.test/alice.png',
  },
}

const hiddenUser: ChatifyUser = {
  type: 'user',
  id: 2,
  attributes: {
    name: HIDDEN_USER_NAME,
    avatar: 'https://example.test/default.png',
    is_identity_hidden: true,
  },
}

describe('userDisplay', () => {
  it('returns Unknown User for hidden identities', () => {
    expect(displayUserName(hiddenUser)).toBe(HIDDEN_USER_NAME)
    expect(isIdentityHidden(hiddenUser)).toBe(true)
  })

  it('returns real name and avatar for visible users', () => {
    expect(displayUserName(visibleUser)).toBe('Alice')
    expect(displayUserAvatar(visibleUser, 'https://example.test/default.png')).toBe('https://example.test/alice.png')
    expect(isIdentityHidden(visibleUser)).toBe(false)
  })

  it('masks user identity with default avatar', () => {
    const masked = maskUserIdentity(visibleUser, 'https://example.test/default.png')

    expect(masked.attributes.name).toBe(HIDDEN_USER_NAME)
    expect(masked.attributes.avatar).toBe('https://example.test/default.png')
    expect(masked.attributes.is_identity_hidden).toBe(true)
  })

  it('sanitizes direct conversations for users who blocked the viewer', () => {
    const conversation: ChatifyConversation = {
      type: 'conversation',
      id: 'c1',
      attributes: {
        conversation_type: 'direct',
        name: null,
        unread_count: 0,
        created_at: '2026-01-01T10:00:00Z',
        updated_at: '2026-01-01T10:00:00Z',
      },
      relationships: {
        participants: [],
        last_message: null,
        other_user: visibleUser,
      },
    }

    const sanitized = sanitizeConversationIdentity(conversation, {
      defaultAvatarUrl: 'https://example.test/default.png',
      isBlockedByMe: () => false,
      isMessagingBlocked: (userId) => String(userId) === '1',
    })

    expect(sanitized.relationships.other_user?.attributes.name).toBe(HIDDEN_USER_NAME)
    expect(sanitized.relationships.other_user?.attributes.is_identity_hidden).toBe(true)
  })
})
