<script setup lang="ts">
import { useChatifyI18n } from '../../composables/useChatifyI18n'

defineProps<{
  count: number
}>()

defineEmits<{
  click: []
}>()

const { t } = useChatifyI18n()
</script>

<template>
  <button
    type="button"
    class="chatify-scroll-bottom-btn"
    :aria-label="t('ui.thread.scroll.to_latest')"
    @click="$emit('click')"
  >
    <span
      v-if="count > 0"
      class="chatify-scroll-bottom-count"
    >
      {{ count > 99 ? '99+' : count }}
    </span>
    <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>
</template>
