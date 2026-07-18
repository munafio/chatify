import { computed } from 'vue'
import { useConfigStore } from '../stores/config'

export type ChatSoundEvent = 'incomingMessage' | 'outgoingMessage' | 'typing'

const audioCache = new Map<string, HTMLAudioElement>()
let unlocked = false

export function unlockChatAudio() {
  if (unlocked || typeof window === 'undefined') {
    return
  }

  unlocked = true

  const configStore = useConfigStore()
  const sounds = configStore.boot?.sounds
  if (!sounds) {
    return
  }

  for (const key of ['incomingMessage', 'outgoingMessage', 'typing'] as const) {
    const url = resolveUrl(sounds[key]?.url)
    if (url) {
      const audio = getAudio(url)
      void audio.play().then(() => {
        audio.pause()
        audio.currentTime = 0
      }).catch(() => {})
    }
  }
}

function resolveUrl(url: string | null | undefined): string | null {
  if (!url) {
    return null
  }

  if (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('data:')) {
    return url
  }

  if (typeof window === 'undefined') {
    return url
  }

  return new URL(url, window.location.origin).toString()
}

function getAudio(url: string): HTMLAudioElement {
  const cached = audioCache.get(url)
  if (cached) {
    return cached
  }

  const audio = new Audio(url)
  audio.preload = 'auto'
  audioCache.set(url, audio)

  return audio
}

export function useChatSounds() {
  const configStore = useConfigStore()

  const soundsEnabled = computed(() => configStore.soundsEnabled)

  function isEventEnabled(event: ChatSoundEvent): boolean {
    if (!soundsEnabled.value) {
      return false
    }

    const sounds = configStore.boot?.sounds
    if (!sounds) {
      return false
    }

    const eventConfig = sounds[event]
    return Boolean(eventConfig?.enabled && eventConfig.url)
  }

  function play(event: ChatSoundEvent) {
    playChatSound(event)
  }

  if (typeof window !== 'undefined') {
    const unlock = () => unlockChatAudio()
    window.addEventListener('pointerdown', unlock, { once: true })
    window.addEventListener('keydown', unlock, { once: true })
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        unlockChatAudio()
      }
    })
  }

  return {
    soundsEnabled,
    isEventEnabled,
    play,
    unlockAudio: unlockChatAudio,
  }
}

export function createTypingSoundPlayer(play: (event: ChatSoundEvent) => void) {
  let lastPlayedAt = 0

  return () => {
    const now = Date.now()
    if (now - lastPlayedAt < 1500) {
      return
    }

    lastPlayedAt = now
    play('typing')
  }
}

export function playChatSound(event: ChatSoundEvent) {
  const configStore = useConfigStore()

  if (!configStore.soundsEnabled) {
    return
  }

  const sounds = configStore.boot?.sounds
  const eventConfig = sounds?.[event]

  if (!sounds?.enabled || !eventConfig?.enabled || !eventConfig.url) {
    return
  }

  const url = resolveUrl(eventConfig.url)
  if (!url) {
    return
  }

  const audio = getAudio(url)
  audio.currentTime = 0
  void audio.play().catch(() => {})
}
