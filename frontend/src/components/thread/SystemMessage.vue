<script setup lang="ts">
defineProps<{
  label: string
}>()
</script>

<template>
  <div class="chatify:flex chatify:justify-center chatify:px-6 chatify:py-1">
    <p class="chatify-system-message">
      {{ label }}
    </p>
  </div>
</template>
