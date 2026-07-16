<script setup lang="ts">
import type { ThemePreset } from '../../themes/types'

defineProps<{
  theme: ThemePreset
  selected: boolean
}>()

defineEmits<{
  select: []
}>()
</script>

<template>
  <button
    type="button"
    class="chatify:flex chatify:flex-1 chatify:flex-col chatify:items-center chatify:gap-2 chatify:p-1"
    @click="$emit('select')"
  >
    <div
      class="chatify:flex chatify:h-24 chatify:w-full chatify:flex-col chatify:justify-end chatify:gap-1 chatify:rounded-xl chatify:border chatify:p-2 chatify:transition"
      :class="selected ? 'chatify-tile-selected' : 'chatify-border-soft'"
      :style="{ backgroundColor: theme.tokens.panel }"
    >
      <div
        class="chatify:h-3 chatify:w-3/4 chatify:rounded-md"
        :style="{ backgroundColor: theme.tokens.bubbleIn }"
      />
      <div
        class="chatify:h-3 chatify:w-1/2 chatify:self-end chatify:rounded-md"
        :style="{ backgroundColor: theme.tokens.bubbleOut }"
      />
    </div>
    <span
      class="chatify:text-xs chatify:font-medium"
      :class="selected ? 'chatify:text-chatify-primary' : 'chatify:text-chatify-muted'"
    >
      {{ theme.name }}
    </span>
    <span
      v-if="selected"
      class="chatify:flex chatify:h-4 chatify:w-4 chatify:items-center chatify:justify-center chatify:rounded-full chatify:border-2 chatify:border-chatify-primary"
    >
      <span class="chatify:h-2 chatify:w-2 chatify:rounded-full chatify:bg-chatify-primary" />
    </span>
  </button>
</template>
