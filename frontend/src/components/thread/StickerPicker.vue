<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useDebouncedWatch } from '../../composables/useDebouncedFn'
import { useGiphy, type GiphySticker } from '../../composables/useGiphy'

const emit = defineEmits<{
  select: [file: File]
  open: []
}>()

const { isEnabled, searchStickers, trendingStickers, fetchStickerAsFile } = useGiphy()

const open = ref(false)
const query = ref('')
const stickers = ref<GiphySticker[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const selectingId = ref<string | null>(null)

const root = ref<HTMLElement | null>(null)

const skeletonTiles = Array.from({ length: 12 })

async function loadStickers(search = '') {
  if (!isEnabled.value) {
    stickers.value = []
    return
  }

  loading.value = true
  error.value = null

  try {
    stickers.value = search.trim()
      ? await searchStickers(search)
      : await trendingStickers()
  } catch {
    error.value = 'Could not load stickers'
    stickers.value = []
  } finally {
    loading.value = false
  }
}

function toggle() {
  if (!isEnabled.value) {
    return
  }
  const next = !open.value
  open.value = next
  if (next) {
    emit('open')
  }
}

function close() {
  open.value = false
}

async function selectSticker(sticker: GiphySticker) {
  if (selectingId.value) {
    return
  }

  selectingId.value = sticker.id
  try {
    const file = await fetchStickerAsFile(sticker)
    close()
    emit('select', file)
  } catch {
    error.value = 'Could not send sticker'
  } finally {
    selectingId.value = null
  }
}

function onDocumentClick(event: MouseEvent) {
  if (!open.value) {
    return
  }

  if (root.value && !root.value.contains(event.target as Node)) {
    close()
  }
}

watch(open, async (isOpen) => {
  if (isOpen) {
    query.value = ''
    await loadStickers()
  }
})

useDebouncedWatch(query, (value) => {
  if (open.value) {
    void loadStickers(value)
  }
})

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
})

defineExpose({ close, toggle })
</script>

<template>
  <div ref="root" class="chatify-composer-popover-anchor chatify:shrink-0">
    <button
      type="button"
      class="chatify-composer-icon-btn"
      :class="open ? 'chatify-composer-icon-btn-active' : ''"
      aria-label="Stickers"
      :disabled="!isEnabled"
      :aria-expanded="open"
      @click.stop="toggle"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </button>

    <div
      v-if="open"
      class="chatify-sticker-picker chatify-composer-popover"
    >
      <div class="chatify-sticker-picker-search">
        <input
          v-model="query"
          type="search"
          placeholder="Search stickers"
          class="chatify-sticker-picker-input"
        />
      </div>

      <div class="chatify-sticker-picker-scroll">
        <div v-if="loading" class="chatify-sticker-picker-grid">
          <div
            v-for="(_, index) in skeletonTiles"
            :key="index"
            class="chatify-sticker-picker-skeleton chatify-attachment-skeleton"
          />
        </div>
        <div v-else-if="error" class="chatify-sticker-picker-state">{{ error }}</div>
        <div v-else-if="stickers.length === 0" class="chatify-sticker-picker-state">No stickers found</div>
        <div v-else class="chatify-sticker-picker-grid">
          <button
            v-for="sticker in stickers"
            :key="sticker.id"
            type="button"
            class="chatify-sticker-picker-item"
            :disabled="selectingId === sticker.id"
            @click="selectSticker(sticker)"
          >
            <img :src="sticker.previewUrl" :alt="sticker.title" loading="lazy" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
