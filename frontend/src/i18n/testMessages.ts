import rawMessages from './testMessages.json'
import { laravelToVueI18n } from './laravelPlaceholders'

export const testMessages = laravelToVueI18n(rawMessages) as Record<string, unknown>
