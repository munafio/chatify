<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { FONT_OPTIONS } from '../../themes/fonts'
import { useConfigStore } from '../../stores/config'

const configStore = useConfigStore()
const { preferences } = storeToRefs(configStore)

const query = ref('')

const filteredFonts = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) {
    return FONT_OPTIONS
  }

  return FONT_OPTIONS.filter((font) => font.label.toLowerCase().includes(q))
})

function selectFont(fontId: string) {
  configStore.setFontFamily(fontId)
}
</script>

<template>
  <div class="chatify:space-y-3">
    <input
      v-model="query"
      type="search"
      placeholder="Search"
      class="chatify-settings-input chatify:w-full chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:text-sm chatify:text-chatify-text"
    />

    <div class="chatify:max-h-64 chatify:overflow-y-auto chatify:overflow-x-hidden chatify:rounded-xl chatify:border chatify-border-soft">
      <button
        v-for="font in filteredFonts"
        :key="font.id"
        type="button"
        class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:border-b chatify-border-soft chatify:px-4 chatify:py-3 chatify:text-left chatify:last:border-b-0"
        @click="selectFont(font.id)"
      >
        <span
          class="chatify:flex chatify:h-4 chatify:w-4 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:border-2"
          :class="preferences.fontFamily === font.id ? 'chatify:border-chatify-primary' : 'chatify:border-chatify-muted'"
        >
          <span
            v-if="preferences.fontFamily === font.id"
            class="chatify:h-2 chatify:w-2 chatify:rounded-full chatify:bg-chatify-primary"
          />
        </span>
        <span class="chatify:truncate chatify:text-sm chatify:text-chatify-text" :style="{ fontFamily: font.stack }">{{ font.label }}</span>
      </button>
    </div>
  </div>
</template>
