import { describe, expect, it } from 'vitest'
import { firstLinkUrl, linkify } from './linkify'

describe('linkify', () => {
  it('returns plain text when no links are present', () => {
    expect(linkify('Hello world')).toEqual([{ type: 'text', value: 'Hello world' }])
  })

  it('splits text around URLs', () => {
    expect(linkify('See https://example.com now')).toEqual([
      { type: 'text', value: 'See ' },
      { type: 'link', value: 'https://example.com', href: 'https://example.com' },
      { type: 'text', value: ' now' },
    ])
  })

  it('extracts the first URL from a message body', () => {
    expect(firstLinkUrl('Check https://a.test and https://b.test')).toBe('https://a.test')
  })

  it('extracts the first URL after linkify has run', () => {
    const body = 'See https://example.com now'
    linkify(body)
    expect(firstLinkUrl(body)).toBe('https://example.com')
  })
})
