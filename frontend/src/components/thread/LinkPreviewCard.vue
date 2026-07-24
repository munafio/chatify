<script setup lang="ts">
import type { LinkPreview } from '../../types'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

defineProps<{
  preview: LinkPreview
}>()

const { t } = useChatifyI18n()
</script>

<template>
  <a
    :href="preview.url"
    target="_blank"
    rel="noopener noreferrer"
    class="chatify-link-preview-card"
  >
    <img
      v-if="preview.image"
      :src="preview.image"
      :alt="preview.title ?? preview.site_name ?? t('ui.thread.bubble.link_preview')"
      class="chatify-link-preview-card-image"
    />
    <div class="chatify:space-y-0.5 chatify:p-2">
      <p
        v-if="preview.site_name"
        class="chatify:text-[10px] chatify:font-medium chatify:uppercase chatify:tracking-wide chatify:text-chatify-muted"
      >
        {{ preview.site_name }}
      </p>
      <p
        v-if="preview.title"
        class="chatify:line-clamp-2 chatify:text-xs chatify:font-semibold chatify:text-chatify-text"
      >
        {{ preview.title }}
      </p>
      <p
        v-if="preview.description"
        class="chatify:line-clamp-2 chatify:text-[11px] chatify:text-chatify-muted"
      >
        {{ preview.description }}
      </p>
    </div>
  </a>
</template>
