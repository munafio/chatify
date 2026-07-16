import { defineStore } from 'pinia'
import { ref } from 'vue'
import { preferencesForServer } from '../composables/useBootConfig'
import { extractErrorMessage } from '../utils/errors'
import { useConfigStore } from './config'
import { useToastStore } from './toast'

export const useSettingsStore = defineStore('settings', () => {
  const saving = ref(false)

  const configStore = useConfigStore()
  const toastStore = useToastStore()

  async function saveAll() {
    if (!configStore.api) {
      return false
    }

    saving.value = true

    try {
      if (configStore.pendingWallpaperFile) {
        const form = new FormData()
        form.append('background', configStore.pendingWallpaperFile)
        const { data } = await configStore.api.uploadChatBackground(form)
        const url = data.data.attributes.chat_background_url
        if (url) {
          configStore.draftPreferences.wallpaper.imageUrl = `${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`
          configStore.draftPreferences.wallpaper.kind = 'image'
        }
      }

      await configStore.api.patchSettings({
        theme_preferences: preferencesForServer(configStore.draftPreferences),
      })

      configStore.commitSaved(configStore.draftPreferences)
      return true
    } catch (err) {
      toastStore.show({
        message: extractErrorMessage(err, 'Failed to save settings'),
        icon: 'error',
        placement: 'bottom',
      })
      return false
    } finally {
      saving.value = false
    }
  }

  return {
    saving,
    saveAll,
  }
})
