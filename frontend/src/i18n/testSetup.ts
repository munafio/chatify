import { config } from '@vue/test-utils'
import { createI18n } from 'vue-i18n'
import { beforeEach } from 'vitest'
import { setBootLocale } from './bootLocale'
import { setChatifyTranslator } from './nonComponent'
import { testMessages } from './testMessages'

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: { en: testMessages as Record<string, Record<string, string>> },
})

config.global.plugins = [i18n]

beforeEach(() => {
  setBootLocale('en')
  setChatifyTranslator((key, params) => i18n.global.t(key, params ?? {}))
})
