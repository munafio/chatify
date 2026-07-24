<script setup lang="ts">
import type { MessageAttachment } from '../../types'
import { useImageLightbox } from '../../composables/useImageLightbox'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

defineProps<{
  attachments: MessageAttachment[]
}>()

const { show } = useImageLightbox()
const { t } = useChatifyI18n()

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
    class="chatify:mb-1 chatify:grid chatify:w-full chatify:max-w-[11rem] chatify:gap-0.5 chatify:overflow-hidden chatify:rounded-lg"
    :class="attachments.length === 1 ? 'chatify:grid-cols-1' : 'chatify:grid-cols-2'"
  >
    <button
      v-for="(item, index) in visibleAttachments(attachments)"
      :key="item.filename"
      type="button"
      class="chatify:relative chatify:aspect-square chatify:w-full chatify:overflow-hidden chatify:bg-black/10"
      @click="openAt(attachments, index)"
    >
      <img :src="item.url" :alt="item.original_name ?? t('ui.thread.bubble.image')" class="chatify:h-full chatify:w-full chatify:object-cover" />
      <div
        v-if="index === 3 && overflowCount(attachments) > 0"
        class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/55 chatify:text-sm chatify:font-semibold chatify:text-white"
      >
        +{{ overflowCount(attachments) }}
      </div>
    </button>
  </div>
</template>
