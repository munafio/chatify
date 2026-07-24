<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { MessageAttachment } from '../../types'
import { clearActiveVoice, setActiveVoice } from '../../utils/voicePlayback'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  attachment: MessageAttachment
  uploading?: boolean
  progress?: number | null
}>()

const emit = defineEmits<{
  cancel: []
}>()

const { t } = useChatifyI18n()

const playing = ref(false)
const ready = ref(false)
const decodeFailed = ref(false)
const duration = ref(0)
const currentTime = ref(0)
const scrubbing = ref(false)
const resumeAfterScrub = ref(false)

let audioContext: AudioContext | null = null
let audioBuffer: AudioBuffer | null = null
let sourceNode: AudioBufferSourceNode | null = null
let startedAt = 0
let pausedOffset = 0
let rafId: number | null = null
let stoppingSource = false

const nativeAudioRef = ref<HTMLAudioElement | null>(null)

const waveBars = [
  38, 62, 45, 80, 55, 70, 40, 90, 60, 48, 75, 52, 68, 42, 85, 58, 50, 72, 46, 64, 36, 78, 54, 66,
]

const hasDuration = computed(() => duration.value > 0 && Number.isFinite(duration.value))

const UPLOAD_RING_CIRCUMFERENCE = 94.25

const uploadDashOffset = computed(() => {
  const percent = Math.min(100, Math.max(0, props.progress ?? 0))
  return UPLOAD_RING_CIRCUMFERENCE - (UPLOAD_RING_CIRCUMFERENCE * percent) / 100
})

const progress = computed(() => {
  if (!hasDuration.value) {
    return 0
  }
  return Math.min(100, (currentTime.value / duration.value) * 100)
})

const remaining = computed(() => {
  if (!hasDuration.value) {
    return 0
  }
  return Math.max(0, duration.value - currentTime.value)
})

const durationLabel = computed(() => {
  if (!hasDuration.value) {
    return '0:00'
  }
  if (playing.value || scrubbing.value || currentTime.value > 0) {
    return formatTime(remaining.value)
  }
  return formatTime(duration.value)
})

function formatTime(seconds: number): string {
  if (!Number.isFinite(seconds)) {
    return '0:00'
  }
  const total = Math.max(0, Math.round(seconds))
  const minutes = Math.floor(total / 60)
  const remainder = total % 60
  return `${minutes}:${String(remainder).padStart(2, '0')}`
}

function ensureContext(): AudioContext {
  if (!audioContext) {
    const Ctor =
      window.AudioContext ??
      (window as unknown as { webkitAudioContext?: typeof AudioContext }).webkitAudioContext
    audioContext = new Ctor!()
  }
  return audioContext
}

function resolveFetchUrl(rawUrl: string): string {
  try {
    const parsed = new URL(rawUrl, window.location.href)
    const localHosts = new Set(['localhost', '127.0.0.1', '0.0.0.0', '[::1]', '::1'])
    const sameServer =
      parsed.hostname === window.location.hostname ||
      (localHosts.has(parsed.hostname) && localHosts.has(window.location.hostname))

    if (sameServer) {
      return window.location.origin + parsed.pathname + parsed.search
    }
    return rawUrl
  } catch {
    return rawUrl
  }
}

async function loadBuffer(url: string) {
  ready.value = false
  decodeFailed.value = false

  try {
    const response = await fetch(resolveFetchUrl(url), { credentials: 'same-origin' })
    if (!response.ok) {
      throw new Error('fetch failed')
    }
    const arrayBuffer = await response.arrayBuffer()
    const ctx = ensureContext()
    audioBuffer = await ctx.decodeAudioData(arrayBuffer)
    duration.value = audioBuffer.duration
    pausedOffset = 0
    currentTime.value = 0
    ready.value = true
  } catch {
    decodeFailed.value = true
  }
}

function currentPlaybackTime(): number {
  if (!audioContext || !playing.value) {
    return pausedOffset
  }
  return Math.min(duration.value, pausedOffset + (audioContext.currentTime - startedAt))
}

function stopTick() {
  if (rafId !== null) {
    cancelAnimationFrame(rafId)
    rafId = null
  }
}

function startTick() {
  stopTick()
  const loop = () => {
    if (!playing.value) {
      return
    }
    const time = currentPlaybackTime()
    currentTime.value = time
    if (time >= duration.value) {
      handleEnded()
      return
    }
    rafId = requestAnimationFrame(loop)
  }
  rafId = requestAnimationFrame(loop)
}

function stopSource() {
  if (sourceNode) {
    stoppingSource = true
    try {
      sourceNode.onended = null
      sourceNode.stop()
    } catch {
      void 0
    }
    try {
      sourceNode.disconnect()
    } catch {
      void 0
    }
    sourceNode = null
    stoppingSource = false
  }
}

function handleEnded() {
  playing.value = false
  pausedOffset = 0
  currentTime.value = 0
  stopTick()
  clearActiveVoice(stopPlayback)
}

function stopPlayback() {
  if (!playing.value) {
    return
  }
  if (decodeFailed.value) {
    nativeAudioRef.value?.pause()
    playing.value = false
  } else {
    pauseWebAudio()
  }
}

function playWebAudio() {
  if (!audioBuffer) {
    return
  }
  const ctx = ensureContext()
  void ctx.resume()
  stopSource()

  const source = ctx.createBufferSource()
  source.buffer = audioBuffer
  source.connect(ctx.destination)
  source.onended = () => {
    if (!stoppingSource) {
      handleEnded()
    }
  }

  const offset = Math.min(Math.max(0, pausedOffset), duration.value)
  source.start(0, offset)
  sourceNode = source
  startedAt = ctx.currentTime
  playing.value = true
  setActiveVoice(stopPlayback)
  startTick()
}

function pauseWebAudio() {
  pausedOffset = currentPlaybackTime()
  stopSource()
  playing.value = false
  currentTime.value = pausedOffset
  stopTick()
  clearActiveVoice(stopPlayback)
}

function seekTo(time: number) {
  const clamped = Math.min(Math.max(0, time), duration.value)
  pausedOffset = clamped
  currentTime.value = clamped

  if (decodeFailed.value) {
    const audio = nativeAudioRef.value
    if (audio) {
      audio.currentTime = clamped
    }
    return
  }

  if (playing.value) {
    stopSource()
    playWebAudio()
  }
}

function togglePlayback() {
  if (decodeFailed.value) {
    const audio = nativeAudioRef.value
    if (!audio) {
      return
    }
    if (playing.value) {
      audio.pause()
      playing.value = false
      clearActiveVoice(stopPlayback)
    } else {
      void audio.play()
      playing.value = true
      setActiveVoice(stopPlayback)
    }
    return
  }

  if (playing.value) {
    pauseWebAudio()
  } else {
    playWebAudio()
  }
}

function ratioFromClientX(clientX: number, el: HTMLElement): number {
  const rect = el.getBoundingClientRect()
  if (rect.width <= 0) {
    return 0
  }
  return Math.min(1, Math.max(0, (clientX - rect.left) / rect.width))
}

function applyScrub(clientX: number, el: HTMLElement) {
  if (!hasDuration.value) {
    return
  }
  currentTime.value = ratioFromClientX(clientX, el) * duration.value
}

function onPointerDown(event: PointerEvent) {
  if (!hasDuration.value) {
    return
  }
  scrubbing.value = true

  if (playing.value) {
    resumeAfterScrub.value = true
    if (decodeFailed.value) {
      nativeAudioRef.value?.pause()
      playing.value = false
    } else {
      pauseWebAudio()
    }
  }

  const el = event.currentTarget as HTMLElement
  try {
    el.setPointerCapture(event.pointerId)
  } catch {
    void 0
  }
  applyScrub(event.clientX, el)
}

function onPointerMove(event: PointerEvent) {
  if (!scrubbing.value) {
    return
  }
  applyScrub(event.clientX, event.currentTarget as HTMLElement)
}

function endScrub(event: PointerEvent) {
  if (!scrubbing.value) {
    return
  }
  scrubbing.value = false

  const el = event.currentTarget as HTMLElement
  try {
    el.releasePointerCapture(event.pointerId)
  } catch {
    void 0
  }

  seekTo(currentTime.value)

  if (resumeAfterScrub.value) {
    resumeAfterScrub.value = false
    if (decodeFailed.value) {
      void nativeAudioRef.value?.play()
      playing.value = true
      setActiveVoice(stopPlayback)
    } else {
      playWebAudio()
    }
  }
}

function onNativeLoaded() {
  const audio = nativeAudioRef.value
  if (audio && Number.isFinite(audio.duration) && audio.duration > 0) {
    duration.value = audio.duration
  }
  ready.value = true
}

function onNativeTime() {
  if (scrubbing.value) {
    return
  }
  currentTime.value = nativeAudioRef.value?.currentTime ?? 0
}

function onNativeEnded() {
  playing.value = false
  currentTime.value = 0
  if (nativeAudioRef.value) {
    nativeAudioRef.value.currentTime = 0
  }
  clearActiveVoice(stopPlayback)
}

onMounted(() => {
  void loadBuffer(props.attachment.url)
})

watch(
  () => props.attachment.url,
  (url) => {
    stopSource()
    playing.value = false
    void loadBuffer(url)
  },
)

onBeforeUnmount(() => {
  clearActiveVoice(stopPlayback)
  stopTick()
  stopSource()
  nativeAudioRef.value?.pause()
  if (audioContext) {
    void audioContext.close()
    audioContext = null
  }
})
</script>

<template>
  <div class="chatify:mb-1 chatify:flex chatify:w-full chatify:min-w-0 chatify:max-w-full chatify:items-center chatify:gap-2">
    <button
      v-if="uploading"
      type="button"
      class="chatify-voice-control chatify-voice-control-uploading"
      :aria-label="t('ui.thread.voice.cancel_sending')"
      @click="emit('cancel')"
    >
      <svg class="chatify-voice-control-spinner chatify:h-8 chatify:w-8" viewBox="0 0 36 36">
        <circle
          class="chatify-voice-control-track"
          cx="18"
          cy="18"
          r="15"
          fill="none"
          stroke-width="3"
        />
        <circle
          class="chatify-voice-control-progress"
          cx="18"
          cy="18"
          r="15"
          fill="none"
          stroke-width="3"
          :style="{
            strokeDasharray: UPLOAD_RING_CIRCUMFERENCE,
            strokeDashoffset: uploadDashOffset,
          }"
        />
      </svg>
      <svg class="chatify-voice-control-x chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>

    <button
      v-else-if="!ready"
      type="button"
      class="chatify-voice-control"
      :aria-label="t('ui.thread.voice.loading')"
      disabled
    >
      <svg class="chatify-voice-loading chatify:h-5 chatify:w-5" viewBox="0 0 24 24" fill="none">
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" stroke-opacity="0.25" />
        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
      </svg>
    </button>

    <button
      v-else
      type="button"
      class="chatify-voice-control"
      :aria-label="playing ? t('ui.thread.voice.pause') : t('ui.thread.voice.play')"
      @click="togglePlayback"
    >
      <svg v-if="!playing" class="chatify:h-4 chatify:w-4" fill="currentColor" viewBox="0 0 24 24">
        <path d="M8 5v14l11-7z" />
      </svg>
      <svg v-else class="chatify:h-4 chatify:w-4" fill="currentColor" viewBox="0 0 24 24">
        <path d="M6 5h4v14H6V5zm8 0h4v14h-4V5z" />
      </svg>
    </button>

    <div class="chatify:min-w-0 chatify:flex-1">
      <div
        class="chatify-voice-wave"
        :class="hasDuration ? 'chatify-voice-wave-seekable' : ''"
        role="slider"
        :aria-label="t('ui.thread.voice.seek')"
        :aria-valuenow="Math.round(progress)"
        aria-valuemin="0"
        aria-valuemax="100"
        @pointerdown="onPointerDown"
        @pointermove="onPointerMove"
        @pointerup="endScrub"
        @pointercancel="endScrub"
      >
        <span
          v-for="(height, index) in waveBars"
          :key="index"
          class="chatify-voice-wave-bar"
          :class="(index / waveBars.length) * 100 <= progress ? 'chatify-voice-wave-bar-played' : ''"
          :style="{ height: `${height}%` }"
        />
      </div>
      <p class="chatify-voice-duration chatify:mt-1 chatify:text-[10px]">
        {{ durationLabel }}
      </p>
    </div>

    <audio
      v-if="decodeFailed"
      ref="nativeAudioRef"
      :src="attachment.url"
      preload="auto"
      class="chatify:hidden"
      @loadedmetadata="onNativeLoaded"
      @canplay="ready = true"
      @timeupdate="onNativeTime"
      @ended="onNativeEnded"
    />
  </div>
</template>
