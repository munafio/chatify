import type { ThemeTokenSet } from './types'

export function tokenSetToCssVars(tokens: ThemeTokenSet): Record<string, string> {
  return {
    'chatify-color-chatify-primary': tokens.primary,
    'chatify-color-chatify-primary-dark': tokens.primaryDark,
    'chatify-color-chatify-sidebar': tokens.sidebar,
    'chatify-color-chatify-panel': tokens.panel,
    'chatify-color-chatify-bubble-out': tokens.bubbleOut,
    'chatify-color-chatify-bubble-in': tokens.bubbleIn,
    'chatify-color-chatify-text': tokens.text,
    'chatify-color-chatify-muted': tokens.muted,
    'chatify-color-chatify-border': tokens.border,
  }
}

export function bubbleTextClass(isOwn: boolean): string {
  return isOwn ? 'chatify:text-chatify-text' : 'chatify:text-chatify-text'
}
