import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createI18n } from 'vue-i18n'
import App from './components/App.vue'
import { applyBootCatalog, parseBootConfig } from './composables/useBootConfig'
import { laravelToVueI18n } from './i18n/laravelPlaceholders'
import { setBootLocale } from './i18n/bootLocale'
import { setChatifyTranslator } from './i18n/nonComponent'
import { useConfigStore } from './stores/config'
import { useContactsStore } from './stores/contacts'
import './style.css'

function mount() {
  const element = document.getElementById('chatify-app')
  if (!element) {
    return
  }

  const config = parseBootConfig(element)
  applyBootCatalog(config)

  const messages = {
    [config.locale]: laravelToVueI18n(config.translations),
    ...(config.fallbackTranslations ? { [config.fallbackLocale]: laravelToVueI18n(config.fallbackTranslations) } : {}),
  }

  const i18n = createI18n({
    legacy: false,
    locale: config.locale,
    fallbackLocale: config.fallbackLocale,
    messages: messages as unknown as Record<string, Record<string, string>>,
  })

  const pinia = createPinia()
  const app = createApp(App, { config })

  app.use(i18n)
  app.use(pinia)

  setChatifyTranslator((key, params) => i18n.global.t(key, params ?? {}))
  setBootLocale(config.locale)

  document.documentElement.dir = config.dir
  element.dir = config.dir

  const configStore = useConfigStore(pinia)
  configStore.init(config)

  const contactsStore = useContactsStore(pinia)
  contactsStore.setMessagingBlockedUserIds(config.messaging_blocked_user_ids ?? [])

  app.mount(element)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount)
} else {
  mount()
}
