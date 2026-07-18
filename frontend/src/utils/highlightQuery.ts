function escapeRegExp(value: string): string {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

export function highlightQuery(text: string | null | undefined, query: string): string {
  const source = text ?? ''
  const trimmed = query.trim()

  if (!trimmed || !source) {
    return escapeHtml(source)
  }

  const pattern = new RegExp(`(${escapeRegExp(trimmed)})`, 'gi')
  return escapeHtml(source).replace(pattern, '<span class="chatify-search-highlight">$1</span>')
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}
