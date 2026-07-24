<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const emit = defineEmits<{
  'pick-media': []
  'pick-document': []
  open: []
}>()

const { t } = useChatifyI18n()
const open = ref(false)
const root = ref<HTMLElement | null>(null)

function toggle() {
  const next = !open.value
  open.value = next
  if (next) {
    emit('open')
  }
}

function close() {
  open.value = false
}

function pickMedia() {
  close()
  emit('pick-media')
}

function pickDocument() {
  close()
  emit('pick-document')
}

function onDocumentClick(event: MouseEvent) {
  if (!open.value) {
    return
  }

  if (root.value && !root.value.contains(event.target as Node)) {
    close()
  }
}

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
      :aria-label="t('ui.thread.composer.attach_menu_open')"
      aria-haspopup="menu"
      :aria-expanded="open"
      @click.stop="toggle"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
    </button>

    <div
      v-if="open"
      class="chatify-composer-attach-menu chatify-profile-menu chatify-composer-popover"
      role="menu"
    >
      <button type="button" role="menuitem" class="chatify-composer-attach-item" @click="pickMedia">
        <span class="chatify-composer-attach-icon chatify-composer-attach-icon-media">
          <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </span>
        <span>{{ $t('ui.thread.composer.photos_videos') }}</span>
      </button>
      <button type="button" role="menuitem" class="chatify-composer-attach-item" @click="pickDocument">
        <span class="chatify-composer-attach-icon chatify-composer-attach-icon-doc">
          <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </span>
        <span>{{ $t('ui.thread.composer.document') }}</span>
      </button>
    </div>
  </div>
</template>
