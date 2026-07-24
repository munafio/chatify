const UUID_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i

export function normalizeWebPath(webBase: string): string {
  const trimmed = webBase.trim()
  if (!trimmed) {
    return ''
  }

  try {
    const url = new URL(trimmed, 'http://localhost')
    return url.pathname.replace(/\/$/, '')
  } catch {
    return trimmed.replace(/\/$/, '')
  }
}

export function buildConversationPath(webBase: string, conversationId: string | null): string {
  const base = normalizeWebPath(webBase)

  if (!conversationId) {
    return base || '/'
  }

  return `${base}/${conversationId}`
}

export function parseConversationIdFromLocation(
  webBase: string,
  pathname = typeof window !== 'undefined' ? window.location.pathname : '',
): string | null {
  const base = normalizeWebPath(webBase)
  const normalizedPath = pathname.replace(/\/$/, '') || '/'

  if (base) {
    if (normalizedPath === base) {
      return null
    }

    if (!normalizedPath.startsWith(`${base}/`)) {
      return null
    }

    const segment = normalizedPath.slice(base.length + 1).split('/')[0]
    return segment && UUID_PATTERN.test(segment) ? segment : null
  }

  const segment = normalizedPath.replace(/^\//, '').split('/')[0]
  if (!segment || !UUID_PATTERN.test(segment)) {
    return null
  }

  return segment
}

export function currentConversationPath(webBase: string): string {
  return buildConversationPath(webBase, parseConversationIdFromLocation(webBase))
}
