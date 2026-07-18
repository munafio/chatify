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

defineEmits<{
  navigate: [screen: 'themes' | 'font' | 'wallpaper' | 'blocked']
}>()

const configStore = useConfigStore()
const contactsStore = useContactsStore()
const { preferences, themesEnabled, fontsEnabled, wallpaperEnabled, showOnlineStatus } = storeToRefs(configStore)
const { blockedUsers } = storeToRefs(contactsStore)

const themeLabel = computed(() => getThemeById(preferences.value.themeId).name)
const fontLabel = computed(() => getFontById(preferences.value.fontFamily).label)

const wallpaperLabel = computed(() => {
  const wallpaper = preferences.value.wallpaper
  if (wallpaper.kind === 'none') {
    return 'None'
  }

  if (wallpaper.kind === 'image') {
    return 'Custom photo'
  }

  return getPatternById(wallpaper.patternId)?.name ?? 'Pattern'
})

const blockedContactsLabel = computed(() => {
  const count = blockedUsers.value.length
  if (count === 0) {
    return 'None'
  }

  return count === 1 ? '1 contact' : `${count} contacts`
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
        label="Show online status"
        :value="showOnlineStatus ? 'On' : 'Off'"
        toggle
        :toggle-on="showOnlineStatus"
        @toggle="toggleOnlineStatus"
      />
      <SettingsRow
        v-if="themesEnabled"
        label="Themes"
        :value="themeLabel"
        chevron
        @click="$emit('navigate', 'themes')"
      />
      <SettingsRow
        v-if="fontsEnabled"
        label="Font"
        :value="fontLabel"
        chevron
        @click="$emit('navigate', 'font')"
      />
      <SettingsRow
        v-if="wallpaperEnabled"
        label="Wallpaper"
        :value="wallpaperLabel"
        chevron
        @click="$emit('navigate', 'wallpaper')"
      />
      <SettingsRow
        label="Blocked contacts"
        :value="blockedContactsLabel"
        chevron
        @click="$emit('navigate', 'blocked')"
      />
    </SettingsGroup>
  </div>
</template>
