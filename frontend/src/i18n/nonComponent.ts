type ChatifyTranslator = (key: string, params?: Record<string, unknown>) => string

let translator: ChatifyTranslator | null = null

export function setChatifyTranslator(t: ChatifyTranslator): void {
  translator = t
}

export function chatifyT(key: string, params?: Record<string, unknown>): string {
  if (translator) {
    return translator(key, params)
  }

  return key
}
