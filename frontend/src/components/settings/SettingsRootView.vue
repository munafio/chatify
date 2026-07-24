<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import SettingsGroup from './SettingsGroup.vue'
import SettingsRow from './SettingsRow.vue'
import SettingsProfileHeader from './SettingsProfileHeader.vue'
import { getThemeById } from '../../themes/presets'
import { getFontById } from '../../themes/fonts'
import { getPatternById } from '../../themes/patterns'
import { useConfigStore } from '../../stores/config'
import { useContactsStore } from '../../stores/contacts'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

defineEmits<{
  navigate: [screen: 'themes' | 'font' | 'wallpaper' | 'blocked']
}>()

const configStore = useConfigStore()
const contactsStore = useContactsStore()
const { t } = useChatifyI18n()
const { preferences, themesEnabled, fontsEnabled, wallpaperEnabled, showOnlineStatus } = storeToRefs(configStore)
const { blockedUsers } = storeToRefs(contactsStore)

const themeLabel = computed(() => getThemeById(preferences.value.themeId).name)
const fontLabel = computed(() => getFontById(preferences.value.fontFamily).label)

const wallpaperLabel = computed(() => {
  const wallpaper = preferences.value.wallpaper
  if (wallpaper.kind === 'none') {
    return t('ui.settings.none')
  }

  if (wallpaper.kind === 'image') {
    return t('ui.settings.custom_photo')
  }

  return getPatternById(wallpaper.patternId)?.name ?? t('ui.settings.pattern')
})

const blockedContactsLabel = computed(() => {
  const count = blockedUsers.value.length
  if (count === 0) {
    return t('ui.settings.none')
  }

  return count === 1 ? t('ui.settings.contact_count') : t('ui.settings.contacts_count', { n: count })
})

async function toggleOnlineStatus() {
  await configStore.setShowOnlineStatus(!showOnlineStatus.value)
}
</script>

<template>
  <div>
    <SettingsProfileHeader />

    <SettingsGroup>
      <SettingsRow
        :label="t('ui.settings.show_online_status')"
        :value="showOnlineStatus ? t('ui.settings.on') : t('ui.settings.off')"
        toggle
        :toggle-on="showOnlineStatus"
        @toggle="toggleOnlineStatus"
      />
      <SettingsRow
        v-if="themesEnabled"
        :label="t('ui.settings.themes')"
        :value="themeLabel"
        chevron
        @click="$emit('navigate', 'themes')"
      />
      <SettingsRow
        v-if="fontsEnabled"
        :label="t('ui.settings.font')"
        :value="fontLabel"
        chevron
        @click="$emit('navigate', 'font')"
      />
      <SettingsRow
        v-if="wallpaperEnabled"
        :label="t('ui.settings.wallpaper')"
        :value="wallpaperLabel"
        chevron
        @click="$emit('navigate', 'wallpaper')"
      />
      <SettingsRow
        :label="t('ui.settings.blocked_contacts')"
        :value="blockedContactsLabel"
        chevron
        @click="$emit('navigate', 'blocked')"
      />
    </SettingsGroup>
  </div>
</template>
