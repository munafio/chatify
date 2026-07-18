import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './components/App.vue'
import { applyBootCatalog, parseBootConfig } from './composables/useBootConfig'
import { useConfigStore } from './stores/config'
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

  app.mount(element)
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', mount)
} else {
  mount()
}
