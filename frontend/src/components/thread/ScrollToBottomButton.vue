<script setup lang="ts">
defineProps<{
  count: number
}>()

defineEmits<{
  click: []
}>()
</script>

<template>
  <button
    type="button"
    class="chatify:absolute chatify:bottom-24 chatify:right-6 chatify:z-20 chatify:flex chatify:h-11 chatify:w-11 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary chatify:text-white chatify:shadow-lg"
    aria-label="Scroll to latest messages"
    @click="$emit('click')"
  >
    <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
    <span
      v-if="count > 0"
      class="chatify:absolute chatify:-right-1 chatify:-top-1 chatify:flex chatify:h-5 chatify:min-w-5 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-danger chatify:px-1 chatify:text-[10px] chatify:font-semibold"
    >
      {{ count > 99 ? '99+' : count }}
    </span>
  </button>
</template>
