<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>

<template>
  <div class="chatify:sticky chatify:top-2 chatify:z-20 chatify:flex chatify:justify-center chatify:py-2">
    <span class="chatify:rounded-full chatify:bg-black/45 chatify:px-3 chatify:py-1 chatify:text-xs chatify:font-medium chatify:text-white">
      {{ label }}
    </span>
  </div>
</template>
