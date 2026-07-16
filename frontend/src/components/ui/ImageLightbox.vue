<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, watch } from 'vue'
import { useImageLightbox } from '../../composables/useImageLightbox'

const { open, images, activeIndex, close, next, prev } = useImageLightbox()

const current = computed(() => images.value[activeIndex.value] ?? null)

function onKeydown(event: KeyboardEvent) {
  if (!open.value) {
    return
  }
  if (event.key === 'Escape') {
    close()
  } else if (event.key === 'ArrowRight') {
    next()
  } else if (event.key === 'ArrowLeft') {
    prev()
  }
}

watch(open, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && current"
      class="chatify-lightbox"
      role="dialog"
      aria-modal="true"
      aria-label="Image viewer"
      @click.self="close"
    >
      <div class="chatify-lightbox-toolbar">
        <span class="chatify-lightbox-counter">
          {{ activeIndex + 1 }} / {{ images.length }}
        </span>
        <div class="chatify-lightbox-actions">
          <a
            :href="current.url"
            :download="current.original_name ?? current.filename"
            class="chatify-lightbox-btn"
            @click.stop
          >
            Download
          </a>
          <button type="button" class="chatify-lightbox-btn" @click="close">Close</button>
        </div>
      </div>

      <button
        v-if="images.length > 1"
        type="button"
        class="chatify-lightbox-nav chatify-lightbox-nav-prev"
        aria-label="Previous image"
        @click="prev"
      >
        <svg class="chatify-lightbox-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <img
        :src="current.url"
        :alt="current.original_name ?? 'Image'"
        class="chatify-lightbox-image"
        @click.stop
      />

      <button
        v-if="images.length > 1"
        type="button"
        class="chatify-lightbox-nav chatify-lightbox-nav-next"
        aria-label="Next image"
        @click="next"
      >
        <svg class="chatify-lightbox-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  </Teleport>
</template>
