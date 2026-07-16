<script setup lang="ts">
defineEmits<{
  retry: []
}>()
</script>

<template>
  <div class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-2 chatify:px-4 chatify:py-6 chatify:text-center">
    <p class="chatify:text-sm chatify:font-medium chatify:text-chatify-text">Couldn't load messages</p>
    <p class="chatify:text-xs chatify:text-chatify-muted">Check your connection and try again.</p>
    <button
      type="button"
      class="chatify:mt-1 chatify:text-xs chatify:font-medium chatify:text-chatify-primary chatify:underline"
      @click="$emit('retry')"
    >
      Try again
    </button>
  </div>
</template>
