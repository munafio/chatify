import { describe, expect, it, beforeEach } from 'vitest'
import {
  clearLinkPreviewCache,
  getLinkPreview,
  hasLinkPreviewData,
  setLinkPreview,
} from './linkPreviewCache'
import type { LinkPreview } from '../types'

const preview: LinkPreview = {
  url: 'https://example.com/article',
  title: 'Example',
  description: 'An example page',
  image: 'https://example.com/image.jpg',
  site_name: 'Example',
}

describe('linkPreviewCache', () => {
  beforeEach(() => {
    clearLinkPreviewCache()
  })

  it('stores and retrieves previews by url', () => {
    expect(getLinkPreview(preview.url)).toBeUndefined()
    setLinkPreview(preview.url, preview)
    expect(getLinkPreview(preview.url)).toEqual(preview)
  })

  it('detects when preview data is present', () => {
    expect(hasLinkPreviewData(null)).toBe(false)
    expect(hasLinkPreviewData({ url: preview.url })).toBe(false)
    expect(hasLinkPreviewData(preview)).toBe(true)
  })
})
