<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { accentSwatchesForTheme } from '../../themes/presets'
import type { ThemeId } from '../../themes/types'
import { useConfigStore } from '../../stores/config'

const props = defineProps<{
  themeId: ThemeId
}>()

const configStore = useConfigStore()
const { preferences, colors } = storeToRefs(configStore)

const swatches = computed(() => accentSwatchesForTheme(props.themeId, colors.value))

const accent = computed(() => preferences.value.accentColor.toLowerCase())

const isThemeActive = computed(() => preferences.value.themeId === props.themeId)

function isSwatchActive(color: string): boolean {
  return isThemeActive.value && accent.value === color.toLowerCase()
}

const isCustomColor = computed(() => {
  if (!isThemeActive.value) {
    return false
  }

  return !swatches.value.some((color) => color.toLowerCase() === accent.value)
})

function select(color: string) {
  configStore.setTheme(props.themeId)
  configStore.setAccentColor(color)
}

function onCustomColor(event: Event) {
  const value = (event.target as HTMLInputElement).value
  configStore.setTheme(props.themeId)
  configStore.setAccentColor(value)
}

const mixDots = [
  { color: '#833ab4', top: '18%', left: '50%' },
  { color: '#fd1d1d', top: '32%', left: '72%' },
  { color: '#fcb045', top: '58%', left: '68%' },
  { color: '#4ade80', top: '72%', left: '50%' },
  { color: '#22d3ee', top: '58%', left: '32%' },
  { color: '#ec4899', top: '32%', left: '28%' },
  { color: '#6366f1', top: '50%', left: '50%' },
]
</script>

<template>
  <div class="chatify-accent-swatches chatify:flex chatify:w-full chatify:flex-nowrap chatify:items-center">
    <div
      v-for="color in swatches"
      :key="color"
      class="chatify:flex chatify:flex-1 chatify:justify-center"
    >
      <button
        type="button"
        class="chatify-accent-swatch chatify:relative chatify:flex chatify:h-8 chatify:w-8 chatify:shrink-0 chatify:items-center chatify:justify-center"
        :aria-label="`Select accent color ${color}`"
        :aria-pressed="isSwatchActive(color)"
        @click="select(color)"
      >
        <span
          v-if="isSwatchActive(color)"
          class="chatify-accent-swatch-ring chatify:absolute chatify:inset-0 chatify:rounded-full"
          :style="{ borderColor: color }"
        />
        <span
          class="chatify:rounded-full chatify:transition-[width,height]"
          :class="isSwatchActive(color) ? 'chatify:h-3.5 chatify:w-3.5' : 'chatify:h-8 chatify:w-8'"
          :style="{ backgroundColor: color }"
        />
      </button>
    </div>

    <div class="chatify:flex chatify:flex-1 chatify:justify-center">
      <label
        class="chatify-accent-swatch chatify:relative chatify:flex chatify:h-8 chatify:w-8 chatify:cursor-pointer chatify:items-center chatify:justify-center"
        :aria-label="isCustomColor ? `Custom color ${preferences.accentColor}` : 'Pick custom color'"
      >
        <span
          v-if="isCustomColor"
          class="chatify-accent-swatch-ring chatify:absolute chatify:inset-0 chatify:rounded-full"
          :style="{ borderColor: preferences.accentColor }"
        />

        <span class="chatify-accent-swatch-mix chatify:relative chatify:h-8 chatify:w-8 chatify:rounded-full">
          <span
            v-for="(dot, index) in mixDots"
            :key="index"
            class="chatify-accent-swatch-mix-dot chatify:absolute chatify:rounded-full"
            :style="{
              backgroundColor: dot.color,
              top: dot.top,
              left: dot.left,
            }"
          />
        </span>

        <input
          type="color"
          class="chatify:absolute chatify:inset-0 chatify:cursor-pointer chatify:opacity-0"
          :value="preferences.accentColor"
          @input="onCustomColor"
        />
      </label>
    </div>
  </div>
</template>
