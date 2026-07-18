export type ThemeId = string
export type PatternId = string
export type WallpaperKind = 'none' | 'pattern' | 'image'

export interface ThemeTokenSet {
  primary: string
  primaryDark: string
  sidebar: string
  panel: string
  bubbleOut: string
  bubbleIn: string
  text: string
  muted: string
  border: string
}

export interface WallpaperPreferences {
  kind: WallpaperKind
  patternId: PatternId
  imageUrl: string | null
  blurEnabled: boolean
  blurAmount: number
}

export interface ChatifyThemePreferences {
  themeId: ThemeId
  accentColor: string
  fontFamily: string
  wallpaper: WallpaperPreferences
}

export interface ThemePreset {
  id: ThemeId
  name: string
  defaultAccent: string
  tokens: ThemeTokenSet
}

export interface ResolvedTheme {
  tokens: Record<string, string>
  patternTintColor: string
}
