import { describe, expect, it } from 'vitest'
import { highlightQuery } from './highlightQuery'

describe('highlightQuery', () => {
  it('wraps matching text in highlight span', () => {
    expect(highlightQuery('Say as again', 'as')).toBe(
      'Say <span class="chatify-search-highlight">as</span> again',
    )
  })

  it('escapes html in source text', () => {
    expect(highlightQuery('<script>alert(1)</script>', 'script')).toBe(
      '&lt;<span class="chatify-search-highlight">script</span>&gt;alert(1)&lt;/<span class="chatify-search-highlight">script</span>&gt;',
    )
  })

  it('returns escaped text when query is empty', () => {
    expect(highlightQuery('hello world', '   ')).toBe('hello world')
  })

  it('is case insensitive', () => {
    expect(highlightQuery('Hello AS world', 'as')).toBe(
      'Hello <span class="chatify-search-highlight">AS</span> world',
    )
  })
})
