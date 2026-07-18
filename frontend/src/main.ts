import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './components/App.vue'
import { applyBootCatalog, parseBootConfig } from './composables/useBootConfig'
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
  const pinia = createPinia()
  const app = createApp(App, { config })

  app.use(pinia)

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
