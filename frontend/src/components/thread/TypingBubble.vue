<script setup lang="ts">
defineProps<{
  align?: 'left' | 'right'
  avatarUrl?: string
}>()
</script>

<template>
  <div
    class="chatify:flex chatify:w-full chatify:items-end chatify:gap-2"
    :class="align === 'right' ? 'chatify:justify-end' : 'chatify:justify-start'"
  >
    <img
      v-if="avatarUrl"
      :src="avatarUrl"
      alt=""
      class="chatify:h-8 chatify:w-8 chatify:rounded-full chatify:object-cover"
    />
    <div class="chatify-typing-bubble">
      <span class="chatify-typing-dot" />
      <span class="chatify-typing-dot chatify-typing-dot-delay-1" />
      <span class="chatify-typing-dot chatify-typing-dot-delay-2" />
    </div>
  </div>
</template>
