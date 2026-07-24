export function laravelToVueI18n(value: unknown): unknown {
  if (typeof value === 'string') {
    return value.replace(/:(\w+)/g, '{$1}')
  }

  if (value && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value as Record<string, unknown>).map(([key, nested]) => [key, laravelToVueI18n(nested)]),
    )
  }

  return value
}
