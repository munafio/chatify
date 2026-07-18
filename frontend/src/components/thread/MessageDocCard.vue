<script setup lang="ts">
import { computed } from 'vue'
import type { MessageAttachment } from '../../types'

const props = defineProps<{
  attachment: MessageAttachment
  uploading?: boolean
  progress?: number | null
}>()

const emit = defineEmits<{
  cancel: []
}>()

const displayName = computed(() => props.attachment.original_name ?? props.attachment.filename ?? 'Document')

const extension = computed(() => {
  const name = displayName.value
  const dot = name.lastIndexOf('.')
  if (dot === -1 || dot === name.length - 1) {
    return 'FILE'
  }
  return name.slice(dot + 1).toUpperCase()
})
</script>

<template>
  <component
    :is="uploading ? 'div' : 'a'"
    v-bind="uploading ? {} : { href: attachment.url, target: '_blank', rel: 'noopener noreferrer' }"
    class="chatify-doc-card"
  >
    <span class="chatify-doc-card-icon">
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    </span>

    <span class="chatify-doc-card-body">
      <span class="chatify-doc-card-name">{{ displayName }}</span>
      <span class="chatify-doc-card-meta">
        <template v-if="uploading">Uploading {{ progress ?? 0 }}%</template>
        <template v-else>{{ extension }}</template>
      </span>
      <span v-if="uploading" class="chatify-doc-card-progress">
        <span class="chatify-doc-card-progress-bar" :style="{ width: `${progress ?? 0}%` }" />
      </span>
    </span>

    <button
      v-if="uploading"
      type="button"
      class="chatify-doc-card-cancel"
      aria-label="Cancel upload"
      @click.prevent="emit('cancel')"
    >
      ✕
    </button>
  </component>
</template>
