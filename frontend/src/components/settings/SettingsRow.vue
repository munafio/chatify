<script setup lang="ts">
defineProps<{
  label: string
  value?: string
  chevron?: boolean
  disabled?: boolean
}>()

defineEmits<{
  click: []
}>()
</script>

<template>
  <button
    type="button"
    class="chatify-settings-row chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:justify-between chatify:px-4 chatify:py-3 chatify:text-left chatify:transition disabled:chatify:opacity-50"
    :disabled="disabled"
    @click="$emit('click')"
  >
    <span class="chatify:text-sm chatify:text-chatify-text">{{ label }}</span>
    <span class="chatify:flex chatify:items-center chatify:gap-2">
      <span v-if="value" class="chatify:text-xs chatify:text-chatify-muted">{{ value }}</span>
      <slot />
      <svg
        v-if="chevron"
        class="chatify:h-4 chatify:w-4 chatify:text-chatify-muted"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </span>
  </button>
</template>
