let bootLocale = 'en'

export function setBootLocale(locale: string): void {
  bootLocale = locale
}

export function getBootLocale(): string {
  return bootLocale
}

export const TEST_BOOT_I18N = {
  locale: 'en',
  fallbackLocale: 'en',
  dir: 'ltr' as const,
  translations: {},
  fallbackTranslations: null,
}
