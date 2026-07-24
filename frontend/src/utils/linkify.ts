export type LinkifySegment =
  | { type: 'text'; value: string }
  | { type: 'link'; value: string; href: string }

const URL_PATTERN = /https?:\/\/[^\s<>"\])]+/gi

export function linkify(text: string): LinkifySegment[] {
  if (!text) {
    return []
  }

  const segments: LinkifySegment[] = []
  let lastIndex = 0
  const matches = text.matchAll(URL_PATTERN)

  for (const match of matches) {
    const href = match[0]
    const index = match.index ?? 0

    if (index > lastIndex) {
      segments.push({ type: 'text', value: text.slice(lastIndex, index) })
    }

    segments.push({ type: 'link', value: href, href })
    lastIndex = index + href.length
  }

  if (lastIndex < text.length) {
    segments.push({ type: 'text', value: text.slice(lastIndex) })
  }

  if (segments.length === 0) {
    segments.push({ type: 'text', value: text })
  }

  return segments
}

export function firstLinkUrl(text: string | null | undefined): string | null {
  if (!text) {
    return null
  }

  const match = text.match(/https?:\/\/[^\s<>"\])]+/i)
  return match?.[0] ?? null
}
