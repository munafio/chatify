<script setup lang="ts">
defineProps<{
  message: string
}>()

defineEmits<{
  retry: []
}>()
</script>

<template>
  <div class="chatify:flex chatify:flex-col chatify:items-center chatify:justify-center chatify:gap-3 chatify:p-8 chatify:text-center">
    <div class="chatify:flex chatify:h-16 chatify:w-16 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-red-50 chatify:text-chatify-danger">
      <svg class="chatify:h-8 chatify:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <p class="chatify:text-sm chatify:text-chatify-muted">{{ message }}</p>
    <button
      type="button"
      class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-2 chatify:text-sm chatify:text-white"
      @click="$emit('retry')"
    >
      {{ $t('ui.common.try_again') }}
    </button>
  </div>
</template>
