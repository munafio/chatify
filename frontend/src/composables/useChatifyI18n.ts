import { useI18n } from 'vue-i18n'

export function useChatifyI18n() {
  const { t, locale, fallbackLocale, d, n, tm, te, rt, mergeLocaleMessage, setLocaleMessage } = useI18n()

  return {
    t,
    locale,
    fallbackLocale,
    d,
    n,
    tm,
    te,
    rt,
    mergeLocaleMessage,
    setLocaleMessage,
  }
}
