<script setup lang="ts">
import type { LinkPreview } from '../../types'

defineProps<{
  preview: LinkPreview
}>()
</script>

<template>
  <a
    :href="preview.url"
    target="_blank"
    rel="noopener noreferrer"
    class="chatify:mt-2 chatify:block chatify:max-w-[20rem] chatify:overflow-hidden chatify:rounded-md chatify:border chatify:border-black/10 chatify:bg-black/5 chatify:no-underline chatify:transition chatify:hover:bg-black/10"
  >
    <img
      v-if="preview.image"
      :src="preview.image"
      :alt="preview.title ?? preview.site_name ?? 'Link preview'"
      class="chatify:max-h-28 chatify:w-full chatify:object-cover"
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
