import type { BootConfig } from '../types'
import {
  applyPreferences,
  loadPreferences,
  normalizePreferences,
} from './useLocalPreferences'
import { setAllowedFonts } from '../themes/fonts'
import { setAllowedThemes } from '../themes/presets'
import { setWallpaperPatterns } from '../themes/patterns'
import type { ChatifyThemePreferences } from '../themes/types'

export function applyBootCatalog(config: BootConfig): void {
  setWallpaperPatterns(config.wallpaperPatterns ?? [])
  setAllowedThemes(config.themes ?? [])
  setAllowedFonts(config.fonts ?? [])
}

export function parseBootConfig(element: HTMLElement): BootConfig {
  const raw = element.dataset.config

  if (!raw) {
    throw new Error('Chatify boot config missing on #chatify-app')
  }

  return JSON.parse(raw) as BootConfig
}

export {
  applyPreferences,
  clonePreferences,
  defaultPreferences,
  loadPreferences,
  normalizePreferences,
  preferencesForServer,
  resolveTokenSet,
  savePreferences,
} from './useLocalPreferences'

export function hydratePreferencesFromBoot(config: BootConfig): ChatifyThemePreferences {
  applyBootCatalog(config)

  const defaultColor = config.colors[0] ?? '#25d366'
  const local = loadPreferences(defaultColor)

  if (!config.preferences?.theme_preferences) {
    return local
  }

  const server = normalizePreferences(
    config.preferences.theme_preferences as Partial<ChatifyThemePreferences>,
    defaultColor,
  )

  server.wallpaper.imageUrl = config.preferences.chat_background_url ?? server.wallpaper.imageUrl

  return server
}

export function applyThemeColors(config: BootConfig, preferences?: ChatifyThemePreferences): void {
  applyBootCatalog(config)
  const resolved = preferences ?? hydratePreferencesFromBoot(config)
  applyPreferences(resolved)
}
