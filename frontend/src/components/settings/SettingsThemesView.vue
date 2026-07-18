<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import ThemeCard from './ThemeCard.vue'
import AccentSwatchRow from './AccentSwatchRow.vue'
import { useConfigStore } from '../../stores/config'

const configStore = useConfigStore()
const { preferences, themePresets, themesEnabled, colorsEnabled } = storeToRefs(configStore)

const activeThemeId = computed(() => preferences.value.themeId)

function selectTheme(themeId: typeof preferences.value.themeId) {
  configStore.setTheme(themeId)
}
</script>

<template>
  <div class="chatify:min-w-0 chatify:space-y-4 chatify:overflow-hidden">
    <div v-if="themesEnabled" class="chatify:flex chatify:min-w-0 chatify:gap-2">
      <ThemeCard
        v-for="theme in themePresets"
        :key="theme.id"
        :theme="theme"
        :selected="preferences.themeId === theme.id"
        @select="selectTheme(theme.id)"
      />
    </div>

    <AccentSwatchRow v-if="colorsEnabled && themesEnabled" :theme-id="activeThemeId" />
  </div>
</template>
