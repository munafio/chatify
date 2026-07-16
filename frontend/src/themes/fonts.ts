export interface FontOption {
  id: string
  label: string
  stack: string
}

export const FONT_OPTIONS: FontOption[] = [
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

export function getFontById(id: string): FontOption {
  return FONT_OPTIONS.find((font) => font.id === id) ?? FONT_OPTIONS[0]
}
