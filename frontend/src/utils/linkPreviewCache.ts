import type { LinkPreview } from '../types'

const cache = new Map<string, LinkPreview>()

export function hasLinkPreviewData(preview: LinkPreview | null | undefined): boolean {
  if (!preview) {
    return false
  }
  return Boolean(preview.title || preview.description || preview.image)
}

export function getLinkPreview(url: string): LinkPreview | undefined {
  return cache.get(url)
}

export function setLinkPreview(url: string, preview: LinkPreview): void {
  cache.set(url, preview)
}

export function clearLinkPreviewCache(): void {
  cache.clear()
}
