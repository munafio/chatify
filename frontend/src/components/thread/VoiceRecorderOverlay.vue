<script setup lang="ts">
import { computed } from 'vue'
import { formatDurationMs } from '../../utils/format'

const props = defineProps<{
  durationMs: number
  waveform: number[]
}>()

const emit = defineEmits<{
  cancel: []
  send: []
}>()

const timerLabel = computed(() => formatDurationMs(props.durationMs))
</script>

<template>
  <div class="chatify-voice-bar">
    <button
      type="button"
      class="chatify-voice-bar-trash"
      aria-label="Cancel recording"
      @click="emit('cancel')"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
      </svg>
    </button>

    <div class="chatify-voice-bar-center">
      <span class="chatify-voice-bar-dot" aria-hidden="true" />
      <div class="chatify-voice-bar-waveform" aria-hidden="true">
        <span
          v-for="(level, index) in waveform"
          :key="index"
          class="chatify-voice-bar-level"
          :style="{ transform: `scaleY(${Math.max(0.15, level)})` }"
        />
      </div>
      <span class="chatify-voice-bar-timer">{{ timerLabel }}</span>
    </div>

    <button
      type="button"
      class="chatify-voice-bar-send"
      aria-label="Send voice message"
      @click="emit('send')"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
      </svg>
    </button>
  </div>
</template>
