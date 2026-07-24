<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { PATTERN_TILE_SIZE } from '../../themes/patterns'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { preferences, pendingWallpaperPreview, wallpaperPatterns } = storeToRefs(configStore)

const fileInput = ref<HTMLInputElement | null>(null)

const storedImageUrl = computed(
  () => pendingWallpaperPreview.value ?? preferences.value.wallpaper.imageUrl,
)

const hasStoredImage = computed(() => Boolean(storedImageUrl.value))
const showBlurControls = computed(() => preferences.value.wallpaper.kind === 'image')

function selectNone() {
  configStore.setWallpaper({ kind: 'none' })
}

function selectPattern(patternId: string) {
  configStore.setWallpaper({ kind: 'pattern', patternId })
}

function onUploadTileClick() {
  if (hasStoredImage.value && configStore.selectWallpaperImage()) {
    return
  }

  fileInput.value?.click()
}

function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }

  configStore.stageWallpaper(file)
  ;(event.target as HTMLInputElement).value = ''
}

function removeStoredImage(event: Event) {
  event.stopPropagation()
  configStore.clearWallpaperImage()
}

function toggleBlur() {
  configStore.setWallpaper({ blurEnabled: !preferences.value.wallpaper.blurEnabled })
}

function setBlurAmount(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  configStore.setWallpaper({ blurAmount: value })
}

const previewUrl = (url: string) => `url("${url}")`
</script>

<template>
  <div class="chatify:min-w-0 chatify:space-y-4">
    <div class="chatify:grid chatify:min-w-0 chatify:grid-cols-3 chatify:gap-2">
      <button
        type="button"
        class="chatify:flex chatify:h-24 chatify:flex-col chatify:items-center chatify:justify-center chatify:rounded-xl chatify:border chatify:bg-chatify-bubble-in chatify:text-xs chatify:font-medium chatify:transition"
        :class="preferences.wallpaper.kind === 'none'
          ? 'chatify-tile-selected chatify:text-chatify-primary'
          : 'chatify-border-soft chatify:text-chatify-muted'"
        @click="selectNone"
      >
        {{ $t('ui.settings.none') }}
      </button>

      <button
        v-for="pattern in wallpaperPatterns"
        :key="pattern.id"
        type="button"
        class="chatify:relative chatify:h-24 chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:transition"
        :class="preferences.wallpaper.kind === 'pattern' && preferences.wallpaper.patternId === pattern.id
          ? 'chatify-tile-selected'
          : 'chatify-border-soft'"
        @click="selectPattern(pattern.id)"
      >
        <div class="chatify:absolute chatify:inset-0 chatify:bg-chatify-panel" />
        <div
          class="chatify-wallpaper-tile-pattern chatify:absolute chatify:inset-0"
          :style="{
            backgroundColor: 'var(--chatify-color-chatify-muted)',
            maskImage: previewUrl(pattern.url),
            WebkitMaskImage: previewUrl(pattern.url),
            maskSize: PATTERN_TILE_SIZE,
            WebkitMaskSize: PATTERN_TILE_SIZE,
            maskRepeat: 'repeat',
            WebkitMaskRepeat: 'repeat',
          }"
        />
      </button>

      <button
        type="button"
        class="chatify:relative chatify:h-24 chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:transition"
        :class="preferences.wallpaper.kind === 'image'
          ? 'chatify-tile-selected'
          : 'chatify-border-soft'"
        @click="onUploadTileClick"
      >
        <img
          v-if="hasStoredImage"
          :src="storedImageUrl || undefined"
          :alt="t('ui.settings.custom_wallpaper')"
          class="chatify:h-full chatify:w-full chatify:object-cover"
        />
        <div
          v-else
          class="chatify:flex chatify:h-full chatify:w-full chatify:flex-col chatify:items-center chatify:justify-center chatify:bg-chatify-sidebar chatify:text-xs chatify:text-chatify-muted"
        >
          {{ $t('ui.settings.upload') }}
        </div>

        <button
          v-if="hasStoredImage"
          type="button"
          class="chatify:absolute chatify:end-1 chatify:top-1 chatify:flex chatify:h-5 chatify:w-5 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-black/60 chatify:text-white chatify:hover:bg-black/80"
          :aria-label="t('ui.settings.remove_wallpaper')"
          @click="removeStoredImage"
        >
          <svg class="chatify:h-3 chatify:w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </button>

      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        class="chatify:hidden"
        @change="onFileSelected"
      />
    </div>

    <div v-if="showBlurControls" class="chatify:space-y-3 chatify:rounded-xl chatify:border chatify-border-soft chatify:p-3">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-3">
        <span class="chatify:text-sm chatify:text-chatify-text">{{ $t('ui.settings.blur_wallpaper') }}</span>
        <button
          type="button"
          class="chatify-settings-toggle chatify:shrink-0"
          :class="preferences.wallpaper.blurEnabled ? 'chatify-settings-toggle-on' : ''"
          role="switch"
          :aria-checked="preferences.wallpaper.blurEnabled"
          @click="toggleBlur"
        />
      </div>

      <div v-if="preferences.wallpaper.blurEnabled" class="chatify:space-y-2">
        <div class="chatify:flex chatify:items-center chatify:justify-between chatify:text-xs chatify:text-chatify-muted">
          <span>{{ $t('ui.settings.blur_intensity') }}</span>
          <span>{{ preferences.wallpaper.blurAmount }}%</span>
        </div>
        <input
          type="range"
          min="0"
          max="100"
          step="1"
          class="chatify-settings-range"
          :value="preferences.wallpaper.blurAmount"
          @input="setBlurAmount"
        />
      </div>
    </div>
  </div>
</template>
