<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'

const configStore = useConfigStore()
const { preferences } = storeToRefs(configStore)

const showPattern = computed(() => preferences.value.wallpaper.kind === 'pattern')
const showImage = computed(() => preferences.value.wallpaper.kind === 'image')
</script>

<template>
  <div
    class="chatify:pointer-events-none chatify:absolute chatify:inset-0 chatify:z-0 chatify:overflow-hidden"
    aria-hidden="true"
  >
    <div class="chatify-chat-bg-base chatify:absolute chatify:inset-0" />
    <div v-if="showPattern" class="chatify-chat-bg-pattern chatify:absolute chatify:inset-0" />
    <div v-if="showImage" class="chatify-chat-bg-image chatify:absolute chatify:inset-0" />
  </div>
</template>
