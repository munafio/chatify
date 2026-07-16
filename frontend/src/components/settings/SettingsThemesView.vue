<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import ThemeCard from './ThemeCard.vue'
import AccentSwatchRow from './AccentSwatchRow.vue'
import { THEME_PRESETS } from '../../themes/presets'
import { useConfigStore } from '../../stores/config'

const configStore = useConfigStore()
const { preferences } = storeToRefs(configStore)

const activeThemeId = computed(() => preferences.value.themeId)

function selectTheme(themeId: typeof preferences.value.themeId) {
  configStore.setTheme(themeId)
}
</script>

<template>
  <div class="chatify:min-w-0 chatify:space-y-4 chatify:overflow-hidden">
    <div class="chatify:flex chatify:min-w-0 chatify:gap-2">
      <ThemeCard
        v-for="theme in THEME_PRESETS"
        :key="theme.id"
        :theme="theme"
        :selected="preferences.themeId === theme.id"
        @select="selectTheme(theme.id)"
      />
    </div>

    <AccentSwatchRow :theme-id="activeThemeId" />
  </div>
</template>
