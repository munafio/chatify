let highlightTimer: number | null = null

export function jumpToMessage(messageId: string, container?: HTMLElement | null): boolean {
  const scope: ParentNode = container ?? document
  const target = scope.querySelector<HTMLElement>(`[data-message-id="${messageId}"]`)

  if (!target) {
    return false
  }

  target.scrollIntoView({ behavior: 'smooth', block: 'center' })

  if (highlightTimer !== null) {
    window.clearTimeout(highlightTimer)
  }

  target.classList.add('chatify-message-highlight')
  highlightTimer = window.setTimeout(() => {
    target.classList.remove('chatify-message-highlight')
    highlightTimer = null
  }, 1800)

  return true
}
