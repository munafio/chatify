import { chatifyT } from '../i18n/nonComponent'
import type { ChatifyThemePreferences, ThemeId, ThemePreset, ThemeTokenSet } from './types'

const THEME_REGISTRY: ThemePreset[] = [
  {
    id: 'classic',
    name: 'Classic',
    defaultAccent: '#25d366',
    tokens: {
      primary: '#25d366',
      primaryDark: '#128c7e',
      sidebar: '#f0f2f5',
      panel: '#efeae2',
      bubbleOut: '#d9fdd3',
      bubbleIn: '#ffffff',
      text: '#111b21',
      muted: '#667781',
      border: '#dfe3e7',
    },
  },
  {
    id: 'day',
    name: 'Day',
    defaultAccent: '#56a7f5',
    tokens: {
      primary: '#56a7f5',
      primaryDark: '#3d8fd9',
      sidebar: '#eef6fb',
      panel: '#e3f2fd',
      bubbleOut: '#cce7ff',
      bubbleIn: '#ffffff',
      text: '#0d2137',
      muted: '#546e7a',
      border: '#d4e4ef',
    },
  },
  {
    id: 'tinted',
    name: 'Tinted',
    defaultAccent: '#c8956c',
    tokens: {
      primary: '#c8956c',
      primaryDark: '#a8744d',
      sidebar: '#2f241e',
      panel: '#241c17',
      bubbleOut: '#8b6548',
      bubbleIn: '#3d322a',
      text: '#f5e6d3',
      muted: '#c4a88f',
      border: '#3a2f28',
    },
  },
  {
    id: 'night',
    name: 'Night',
    defaultAccent: '#6ab2f2',
    tokens: {
      primary: '#6ab2f2',
      primaryDark: '#4a90d9',
      sidebar: '#17212b',
      panel: '#0e1621',
      bubbleOut: '#2b5278',
      bubbleIn: '#182533',
      text: '#f5f5f5',
      muted: '#708499',
      border: '#1c2b38',
    },
  },
]

let allowedThemeIds: string[] | null = null

function withTranslatedName(theme: ThemePreset): ThemePreset {
  return {
    ...theme,
    name: chatifyT(`themes.${theme.id}`),
  }
}

export function setAllowedThemes(ids: string[]): void {
  allowedThemeIds = ids
}

export function themePresets(): ThemePreset[] {
  if (allowedThemeIds === null) {
    return THEME_REGISTRY.map(withTranslatedName)
  }

  return allowedThemeIds
    .map((id) => THEME_REGISTRY.find((theme) => theme.id === id))
    .filter((theme): theme is ThemePreset => theme !== undefined)
    .map(withTranslatedName)
}

export function defaultThemeId(): string {
  return themePresets()[0]?.id ?? THEME_REGISTRY[0].id
}

export function sanitizeThemeId(id: string | undefined): string {
  if (id && themePresets().some((theme) => theme.id === id)) {
    return id
  }

  return defaultThemeId()
}

export function getThemeById(id: string): ThemePreset {
  const theme = THEME_REGISTRY.find((item) => item.id === id) ?? themePresets()[0] ?? THEME_REGISTRY[0]
  return withTranslatedName(theme)
}

export function isThemeId(id: string): id is ThemeId {
  return themePresets().some((theme) => theme.id === id)
}

export function accentSwatchesForTheme(themeId: ThemeId, bootColors: string[]): string[] {
  const theme = getThemeById(themeId)
  const unique = new Set<string>([
    theme.defaultAccent,
    ...bootColors,
  ])

  return Array.from(unique)
}

export function applyAccentToTokens(base: ThemeTokenSet, accent: string, themeId?: ThemeId): ThemeTokenSet {
  if (themeId === 'tinted' && isDarkAccent(accent)) {
    return applyDarkAccentToTinted(base, accent)
  }

  if (themeId === 'night' && isDarkAccent(accent)) {
    return applyDarkAccentToNight(base, accent)
  }

  const tokens: ThemeTokenSet = {
    ...base,
    primary: accent,
    primaryDark: darkenHex(accent, 0.18),
    bubbleOut: mixHex(base.bubbleOut, accent, 0.35),
  }

  if (themeId === 'tinted') {
    tokens.sidebar = mixHex(base.sidebar, accent, 0.28)
    tokens.panel = mixHex(base.panel, accent, 0.24)
    tokens.bubbleIn = mixHex(base.bubbleIn, accent, 0.2)
    tokens.muted = mixHex(base.muted, accent, 0.12)
    tokens.border = subtleBorder(tokens.sidebar)
  } else if (themeId === 'night') {
    tokens.border = subtleBorder(tokens.sidebar)
  }

  return tokens
}

function applyDarkAccentToTinted(base: ThemeTokenSet, accent: string): ThemeTokenSet {
  const neutral = desaturateHex(accent, 0.92)
  const sidebar = lightenHex(neutral, 0.08)
  const panel = darkenHex(neutral, 0.03)

  return {
    ...base,
    primary: accent,
    primaryDark: darkenHex(accent, 0.15),
    sidebar,
    panel,
    bubbleOut: mixHex(lightenHex(neutral, 0.18), accent, 0.35),
    bubbleIn: lightenHex(neutral, 0.12),
    text: base.text,
    muted: mixHex('#9a9a9a', accent, 0.25),
    border: subtleBorder(sidebar),
  }
}

function applyDarkAccentToNight(base: ThemeTokenSet, accent: string): ThemeTokenSet {
  const neutral = desaturateHex(accent, 0.88)
  const sidebar = lightenHex(neutral, 0.06)
  const panel = darkenHex(neutral, 0.04)

  return {
    ...base,
    primary: accent,
    primaryDark: darkenHex(accent, 0.15),
    sidebar,
    panel,
    bubbleOut: mixHex(lightenHex(neutral, 0.14), accent, 0.4),
    bubbleIn: lightenHex(neutral, 0.1),
    text: base.text,
    muted: mixHex('#7a8694', accent, 0.2),
    border: subtleBorder(sidebar),
  }
}

function isDarkAccent(hex: string): boolean {
  return relativeLuminance(hex) < 0.12
}

function relativeLuminance(hex: string): number {
  const channels = parseHex(hex).map((channel) => {
    const value = channel / 255
    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4
  })

  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2]
}

function parseHex(hex: string): [number, number, number] {
  const normalized = hex.replace('#', '')
  if (normalized.length !== 6) {
    return [0, 0, 0]
  }

  return [0, 2, 4].map((index) => parseInt(normalized.slice(index, index + 2), 16)) as [number, number, number]
}

function desaturateHex(hex: string, amount: number): string {
  const [r, g, b] = parseHex(hex)
  const gray = Math.round(0.2126 * r + 0.7152 * g + 0.0722 * b)
  const weight = Math.min(1, Math.max(0, amount))

  return `#${[r, g, b]
    .map((channel) => Math.round(channel * (1 - weight) + gray * weight))
    .map((channel) => channel.toString(16).padStart(2, '0'))
    .join('')}`
}

function subtleBorder(sidebar: string): string {
  return lightenHex(sidebar, 0.07)
}

export function resolvePatternTint(preferences: ChatifyThemePreferences, tokens: ThemeTokenSet): string {
  if (isDarkAccent(preferences.accentColor)) {
    return mixHex(tokens.muted, preferences.accentColor, 0.25)
  }

  switch (preferences.themeId) {
    case 'tinted':
      return mixHex(tokens.muted, preferences.accentColor, 0.45)
    case 'night':
      return mixHex(tokens.muted, preferences.accentColor, 0.4)
    default:
      return preferences.accentColor
  }
}

function mixHex(base: string, accent: string, weight: number): string {
  const a = parseHex(base)
  const b = parseHex(accent)
  const mixed = a.map((channel, index) => Math.round(channel * (1 - weight) + b[index] * weight))
  return `#${mixed.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`
}

function darkenHex(hex: string, amount: number): string {
  const channels = parseHex(hex)
  const darkened = channels.map((channel) => Math.max(0, Math.round(channel * (1 - amount))))
  return `#${darkened.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`
}

function lightenHex(hex: string, amount: number): string {
  const channels = parseHex(hex)
  const lightened = channels.map((channel) => Math.min(255, Math.round(channel + (255 - channel) * amount)))
  return `#${lightened.map((channel) => channel.toString(16).padStart(2, '0')).join('')}`
}
