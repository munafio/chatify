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

defineEmits<{
  navigate: [screen: 'themes' | 'font' | 'wallpaper']
}>()

const configStore = useConfigStore()
const { preferences, themesEnabled, fontsEnabled, wallpaperEnabled } = storeToRefs(configStore)

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
</script>

<template>
  <div>
    <SettingsProfileHeader />

    <SettingsGroup>
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
    </SettingsGroup>
  </div>
</template>
