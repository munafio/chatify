<script setup lang="ts">
defineProps<{
  align?: 'left' | 'right'
}>()
</script>

<template>
  <div
    class="chatify:flex chatify:w-full"
    :class="align === 'right' ? 'chatify:justify-end' : 'chatify:justify-start'"
  >
    <div class="chatify-typing-bubble">
      <span class="chatify-typing-dot" />
      <span class="chatify-typing-dot chatify-typing-dot-delay-1" />
      <span class="chatify-typing-dot chatify-typing-dot-delay-2" />
    </div>
  </div>
</template>
