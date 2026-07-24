import { describe, expect, it } from 'vitest'
import {
  buildConversationPath,
  normalizeWebPath,
  parseConversationIdFromLocation,
} from './conversationUrl'

const conversationId = '550e8400-e29b-41d4-a716-446655440000'

describe('conversationUrl', () => {
  it('normalizes web base paths', () => {
    expect(normalizeWebPath('http://example.test/chatify/')).toBe('/chatify')
    expect(normalizeWebPath('/chatify')).toBe('/chatify')
  })

  it('builds inbox and conversation paths', () => {
    expect(buildConversationPath('/chatify', null)).toBe('/chatify')
    expect(buildConversationPath('/chatify', conversationId)).toBe(`/chatify/${conversationId}`)
  })

  it('parses conversation ids from the current location', () => {
    expect(parseConversationIdFromLocation('/chatify', '/chatify')).toBeNull()
    expect(parseConversationIdFromLocation('/chatify', `/chatify/${conversationId}`)).toBe(conversationId)
    expect(parseConversationIdFromLocation('/chatify', '/chatify/not-a-uuid')).toBeNull()
    expect(parseConversationIdFromLocation('/chatify', '/other')).toBeNull()
  })

  it('supports full web base urls', () => {
    expect(parseConversationIdFromLocation('http://example.test/chatify', `/chatify/${conversationId}`)).toBe(
      conversationId,
    )
  })
})
