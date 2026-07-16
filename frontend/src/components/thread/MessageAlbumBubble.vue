<script setup lang="ts">
import type { MessageAttachment } from '../../types'
import { useImageLightbox } from '../../composables/useImageLightbox'

defineProps<{
  attachments: MessageAttachment[]
}>()

const { show } = useImageLightbox()

function visibleAttachments(attachments: MessageAttachment[]) {
  return attachments.slice(0, 4)
}

function overflowCount(attachments: MessageAttachment[]) {
  return Math.max(0, attachments.length - 4)
}

function openAt(attachments: MessageAttachment[], index: number) {
  show(attachments, index)
}
</script>

<template>
  <div
    class="chatify:mb-1 chatify:grid chatify:max-w-[11rem] chatify:grid-cols-2 chatify:gap-0.5 chatify:overflow-hidden chatify:rounded-lg"
    :class="attachments.length === 1 ? 'chatify:grid-cols-1' : 'chatify:grid-cols-2'"
  >
    <button
      v-for="(item, index) in visibleAttachments(attachments)"
      :key="item.filename"
      type="button"
      class="chatify:relative chatify:h-16 chatify:w-16 chatify:overflow-hidden chatify:bg-black/10"
      :class="attachments.length === 1 ? 'chatify:h-28 chatify:w-28' : ''"
      @click="openAt(attachments, index)"
    >
      <img :src="item.url" :alt="item.original_name ?? 'Image'" class="chatify:h-full chatify:w-full chatify:object-cover" />
      <div
        v-if="index === 3 && overflowCount(attachments) > 0"
        class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/55 chatify:text-sm chatify:font-semibold chatify:text-white"
      >
        +{{ overflowCount(attachments) }}
      </div>
    </button>
  </div>
</template>
