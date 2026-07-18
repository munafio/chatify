export function mergeById<T extends { id: number | string }>(existing: T[], incoming: T[]): T[] {
  const map = new Map<string, T>()

  for (const item of existing) {
    map.set(String(item.id), item)
  }

  for (const item of incoming) {
    map.set(String(item.id), item)
  }

  return Array.from(map.values())
}
