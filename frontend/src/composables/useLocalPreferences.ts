import { getFontById, sanitizeFontId } from '../themes/fonts'
import {
  applyAccentToTokens,
  defaultThemeId,
  getThemeById,
  isThemeId,
  resolvePatternTint,
  sanitizeThemeId,
} from '../themes/presets'
import {
  defaultPatternId,
  getPatternById,
  PATTERN_TILE_SIZE,
  sanitizePatternId,
} from '../themes/patterns'
import type {
  ChatifyThemePreferences,
  ResolvedTheme,
  ThemeTokenSet,
  WallpaperPreferences,
} from '../themes/types'
import { tokenSetToCssVars } from '../themes/utils'

const STORAGE_KEY = 'chatify.preferences'

export function defaultWallpaper(): WallpaperPreferences {
  return {
    kind: 'none',
    patternId: defaultPatternId(),
    imageUrl: null,
    blurEnabled: false,
    blurAmount: 50,
  }
}

export function defaultPreferences(defaultColor = '#25d366'): ChatifyThemePreferences {
  return {
    themeId: defaultThemeId(),
    accentColor: defaultColor,
    fontFamily: sanitizeFontId('system'),
    wallpaper: defaultWallpaper(),
  }
}

function chatifyRoot(): HTMLElement | null {
  return document.getElementById('chatify-app')
}

function themeTargets(): HTMLElement[] {
  const targets: HTMLElement[] = [document.documentElement, document.body]
  const app = chatifyRoot()
  if (app) {
    targets.push(app)
  }
  return targets
}

function setCssVar(name: string, value: string): void {
  for (const target of themeTargets()) {
    target.style.setProperty(name, value)
  }
}

function removeCssVar(name: string): void {
  for (const target of themeTargets()) {
    target.style.removeProperty(name)
  }
}

export function resolveTokenSet(preferences: ChatifyThemePreferences): ThemeTokenSet {
  const theme = getThemeById(preferences.themeId)
  return applyAccentToTokens(theme.tokens, preferences.accentColor, preferences.themeId)
}

export function resolveTheme(preferences: ChatifyThemePreferences): ResolvedTheme {
  const tokens = resolveTokenSet(preferences)

  return {
    tokens: tokenSetToCssVars(tokens),
    patternTintColor: resolvePatternTint(preferences, tokens),
  }
}

function clampBlurAmount(value: unknown, fallback: number): number {
  const amount = typeof value === 'number' ? value : Number(value)
  if (!Number.isFinite(amount)) {
    return fallback
  }

  return Math.min(100, Math.max(0, Math.round(amount)))
}

function applyWallpaperVars(preferences: ChatifyThemePreferences, patternTintColor: string): void {
  const wallpaper = preferences.wallpaper

  setCssVar('--chatify-chat-bg-enabled', wallpaper.kind === 'none' ? '0' : '1')
  setCssVar('--chatify-chat-bg-pattern-tint', patternTintColor)

  const blurActive = wallpaper.kind === 'image' && wallpaper.blurEnabled
  const blurPx = blurActive ? `${Math.round(wallpaper.blurAmount * 0.3)}px` : '0px'
  setCssVar('--chatify-chat-bg-blur', blurPx)
  setCssVar('--chatify-chat-bg-blur-enabled', blurActive ? '1' : '0')

  if (wallpaper.kind === 'pattern') {
    const pattern = getPatternById(wallpaper.patternId)
    if (pattern) {
      setCssVar('--chatify-chat-bg-pattern-url', `url("${pattern.url}")`)
      setCssVar('--chatify-chat-bg-pattern-size', PATTERN_TILE_SIZE)
    } else {
      removeCssVar('--chatify-chat-bg-pattern-url')
      removeCssVar('--chatify-chat-bg-pattern-size')
    }
    removeCssVar('--chatify-chat-bg-image-url')
  } else if (wallpaper.kind === 'image' && wallpaper.imageUrl) {
    removeCssVar('--chatify-chat-bg-pattern-url')
    removeCssVar('--chatify-chat-bg-pattern-size')
    setCssVar('--chatify-chat-bg-image-url', `url("${wallpaper.imageUrl}")`)
  } else {
    removeCssVar('--chatify-chat-bg-pattern-url')
    removeCssVar('--chatify-chat-bg-pattern-size')
    removeCssVar('--chatify-chat-bg-image-url')
  }
}

function applyFont(preferences: ChatifyThemePreferences): void {
  const font = getFontById(preferences.fontFamily)
  setCssVar('--chatify-font-family', font.stack)
}

export function applyPreferences(preferences: ChatifyThemePreferences): void {
  const resolved = resolveTheme(preferences)

  document.body.classList.toggle('chatify-dark', preferences.themeId === 'night' || preferences.themeId === 'tinted')
  chatifyRoot()?.classList.toggle('chatify-dark', preferences.themeId === 'night' || preferences.themeId === 'tinted')

  for (const [key, value] of Object.entries(resolved.tokens)) {
    setCssVar(`--${key}`, value)
  }

  removeCssVar('--chatify-primary-gradient')
  removeCssVar('--chatify-bubble-out-gradient')
  setCssVar('--chatify-bubble-out-is-gradient', '0')
  document.documentElement.classList.remove('chatify-accent-gradient-mode')

  applyWallpaperVars(preferences, resolved.patternTintColor)
  applyFont(preferences)
}

function migrateLegacy(raw: Record<string, unknown>, defaultColor?: string): ChatifyThemePreferences {
  const base = defaultPreferences(defaultColor)

  if (typeof raw.themeId === 'string' && isThemeId(raw.themeId)) {
    const wallpaperRaw = raw.wallpaper as Partial<WallpaperPreferences> | undefined
    return {
      themeId: raw.themeId,
      accentColor: typeof raw.accentColor === 'string' ? raw.accentColor : base.accentColor,
      fontFamily: typeof raw.fontFamily === 'string' ? sanitizeFontId(raw.fontFamily) : base.fontFamily,
      wallpaper: normalizeWallpaper(wallpaperRaw, base.wallpaper),
    }
  }

  const legacyColor = typeof raw.messengerColor === 'string'
    ? raw.messengerColor
    : typeof raw.accentColor === 'string'
      ? raw.accentColor
      : (raw.accent as { color?: string } | undefined)?.color

  let themeId = base.themeId
  if (typeof raw.presetId === 'string' && isThemeId(raw.presetId)) {
    themeId = raw.presetId
  } else if (raw.darkMode === true) {
    themeId = sanitizeThemeId('night')
  }

  const chatBgRaw = (raw.chatBackground ?? raw.wallpaper) as Record<string, unknown> | undefined
  const wallpaper = normalizeWallpaperFromLegacy(chatBgRaw, base.wallpaper)

  return {
    themeId,
    accentColor: legacyColor ?? getThemeById(themeId).defaultAccent,
    fontFamily: typeof raw.fontFamily === 'string' ? sanitizeFontId(raw.fontFamily) : base.fontFamily,
    wallpaper,
  }
}

function normalizeWallpaper(
  input: Partial<WallpaperPreferences> | undefined,
  fallback: WallpaperPreferences,
): WallpaperPreferences {
  if (!input) {
    return fallback
  }

  const kind = input.kind ?? fallback.kind
  const patternId = typeof input.patternId === 'string'
    ? sanitizePatternId(input.patternId)
    : fallback.patternId

  return {
    kind: kind === 'pattern' || kind === 'image' ? kind : 'none',
    patternId,
    imageUrl: typeof input.imageUrl === 'string' ? input.imageUrl : null,
    blurEnabled: typeof input.blurEnabled === 'boolean' ? input.blurEnabled : fallback.blurEnabled,
    blurAmount: clampBlurAmount(input.blurAmount, fallback.blurAmount),
  }
}

function normalizeWallpaperFromLegacy(
  input: Record<string, unknown> | undefined,
  fallback: WallpaperPreferences,
): WallpaperPreferences {
  if (!input) {
    return fallback
  }

  if (input.kind === 'none' || input.kind === 'pattern' || input.kind === 'image') {
    return normalizeWallpaper(input as Partial<WallpaperPreferences>, fallback)
  }

  const enabled = Boolean(input.enabled)
  const type = input.type
  if (!enabled || type === 'none') {
    return { ...fallback, kind: 'none', imageUrl: null }
  }

  if (type === 'image') {
    return normalizeWallpaper({
      kind: 'image',
      patternId: typeof input.patternId === 'string' ? input.patternId : fallback.patternId,
      imageUrl: typeof input.imageUrl === 'string' ? input.imageUrl : null,
    }, fallback)
  }

  return normalizeWallpaper({
    kind: 'pattern',
    patternId: typeof input.patternId === 'string' ? input.patternId : fallback.patternId,
    imageUrl: null,
  }, fallback)
}

export function normalizePreferences(
  input: Partial<ChatifyThemePreferences> | null | undefined,
  defaultColor?: string,
): ChatifyThemePreferences {
  const base = defaultPreferences(defaultColor)
  if (!input) {
    return base
  }

  return {
    themeId: input.themeId && isThemeId(input.themeId) ? input.themeId : sanitizeThemeId(base.themeId),
    accentColor: input.accentColor ?? base.accentColor,
    fontFamily: sanitizeFontId(input.fontFamily ?? base.fontFamily),
    wallpaper: normalizeWallpaper(input.wallpaper, base.wallpaper),
  }
}

export function loadPreferences(defaultColor?: string): ChatifyThemePreferences {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return defaultPreferences(defaultColor)
    }

    return migrateLegacy(JSON.parse(raw) as Record<string, unknown>, defaultColor)
  } catch {
    return defaultPreferences(defaultColor)
  }
}

export function savePreferences(preferences: ChatifyThemePreferences): void {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences))
}

export function preferencesForServer(preferences: ChatifyThemePreferences): Record<string, unknown> {
  return {
    themeId: preferences.themeId,
    accentColor: preferences.accentColor,
    fontFamily: preferences.fontFamily,
    wallpaper: {
      kind: preferences.wallpaper.kind,
      patternId: preferences.wallpaper.patternId,
      blurEnabled: preferences.wallpaper.blurEnabled,
      blurAmount: preferences.wallpaper.blurAmount,
    },
  }
}

export function clonePreferences(preferences: ChatifyThemePreferences): ChatifyThemePreferences {
  return JSON.parse(JSON.stringify(preferences)) as ChatifyThemePreferences
}

export type { ChatifyThemePreferences as ChatifyPreferences } from '../themes/types'
