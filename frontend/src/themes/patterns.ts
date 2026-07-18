export const PATTERN_TILE_SIZE = '120px'

export interface WallpaperPattern {
  id: string
  name: string
  url: string
}

let patterns: WallpaperPattern[] = []

export function setWallpaperPatterns(list: WallpaperPattern[]): void {
  patterns = list
}

export function wallpaperPatterns(): WallpaperPattern[] {
  return patterns
}

export function defaultPatternId(): string {
  return patterns[0]?.id ?? 'bubbles'
}

export function sanitizePatternId(id: string | undefined): string {
  if (id && patterns.some((pattern) => pattern.id === id)) {
    return id
  }

  return defaultPatternId()
}

export function getPatternById(id: string): WallpaperPattern | null {
  return patterns.find((pattern) => pattern.id === id) ?? patterns[0] ?? null
}
