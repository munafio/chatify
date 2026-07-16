<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>

<template>
  <div class="chatify:flex chatify:justify-center chatify:py-2">
    <span class="chatify:rounded-full chatify:bg-black/45 chatify:px-3 chatify:py-1 chatify:text-xs chatify:font-medium chatify:text-white">
      {{ label }}
    </span>
  </div>
</template>
