export interface FontOption {
  id: string
  label: string
  stack: string
}

const FONT_REGISTRY: FontOption[] = [
  { id: 'system', label: 'System font', stack: 'system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif' },
  { id: 'segoe', label: 'Segoe UI', stack: '"Segoe UI", system-ui, sans-serif' },
  { id: 'arial', label: 'Arial', stack: 'Arial, Helvetica, sans-serif' },
  { id: 'helvetica', label: 'Helvetica', stack: 'Helvetica, Arial, sans-serif' },
  { id: 'georgia', label: 'Georgia', stack: 'Georgia, "Times New Roman", serif' },
  { id: 'times', label: 'Times New Roman', stack: '"Times New Roman", Times, serif' },
  { id: 'courier', label: 'Courier New', stack: '"Courier New", Courier, monospace' },
  { id: 'verdana', label: 'Verdana', stack: 'Verdana, Geneva, sans-serif' },
  { id: 'tahoma', label: 'Tahoma', stack: 'Tahoma, Geneva, sans-serif' },
  { id: 'trebuchet', label: 'Trebuchet MS', stack: '"Trebuchet MS", Helvetica, sans-serif' },
  { id: 'palatino', label: 'Palatino', stack: '"Palatino Linotype", Palatino, serif' },
  { id: 'garamond', label: 'Garamond', stack: 'Garamond, "Times New Roman", serif' },
  { id: 'consolas', label: 'Consolas', stack: 'Consolas, "Courier New", monospace' },
  { id: 'calibri', label: 'Calibri', stack: 'Calibri, "Segoe UI", sans-serif' },
  { id: 'cambria', label: 'Cambria', stack: 'Cambria, Georgia, serif' },
  { id: 'lucida', label: 'Lucida Sans', stack: '"Lucida Sans Unicode", "Lucida Grande", sans-serif' },
  { id: 'impact', label: 'Impact', stack: 'Impact, Haettenschweiler, sans-serif' },
  { id: 'comic', label: 'Comic Sans MS', stack: '"Comic Sans MS", cursive, sans-serif' },
]

let allowedFontIds: string[] | null = null

export function setAllowedFonts(ids: string[]): void {
  allowedFontIds = ids
}

export function fontOptions(): FontOption[] {
  if (allowedFontIds === null) {
    return FONT_REGISTRY
  }

  return allowedFontIds
    .map((id) => FONT_REGISTRY.find((font) => font.id === id))
    .filter((font): font is FontOption => font !== undefined)
}

export function defaultFontId(): string {
  return fontOptions()[0]?.id ?? FONT_REGISTRY[0].id
}

export function sanitizeFontId(id: string | undefined): string {
  if (id && fontOptions().some((font) => font.id === id)) {
    return id
  }

  return defaultFontId()
}

export function getFontById(id: string): FontOption {
  return FONT_REGISTRY.find((font) => font.id === id) ?? fontOptions()[0] ?? FONT_REGISTRY[0]
}
