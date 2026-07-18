import { onBeforeUnmount, ref } from 'vue'

const MAX_DURATION_MS = 5 * 60 * 1000

function pickMimeType(): string | undefined {
  const candidates = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/ogg;codecs=opus',
    'audio/mp4',
  ]

  if (typeof MediaRecorder === 'undefined') {
    return undefined
  }

  return candidates.find((type) => MediaRecorder.isTypeSupported(type))
}

function extensionForMime(mimeType: string): string {
  if (mimeType.includes('ogg')) {
    return 'ogg'
  }
  if (mimeType.includes('mp4')) {
    return 'm4a'
  }
  return 'webm'
}

export function useVoiceRecorder() {
  const isRecording = ref(false)
  const durationMs = ref(0)
  const waveform = ref<number[]>([])
  const cancelled = ref(false)

  let mediaRecorder: MediaRecorder | null = null
  let mediaStream: MediaStream | null = null
  let audioContext: AudioContext | null = null
  let analyser: AnalyserNode | null = null
  let animationFrame: number | null = null
  let durationTimer: number | null = null
  let startedAt = 0
  const chunks: Blob[] = []

  function stopWaveform() {
    if (animationFrame !== null) {
      cancelAnimationFrame(animationFrame)
      animationFrame = null
    }
  }

  function stopDurationTimer() {
    if (durationTimer !== null) {
      window.clearInterval(durationTimer)
      durationTimer = null
    }
  }

  function cleanupStream(options?: { preserveRecorder?: boolean }) {
    stopWaveform()
    stopDurationTimer()

    if (!options?.preserveRecorder) {
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop()
      }
      mediaRecorder = null
    }

    mediaStream?.getTracks().forEach((track) => track.stop())
    mediaStream = null

    if (audioContext) {
      void audioContext.close()
      audioContext = null
    }

    analyser = null
    chunks.length = 0
  }

  function updateWaveform() {
    if (!analyser) {
      return
    }

    const buffer = new Uint8Array(analyser.frequencyBinCount)
    analyser.getByteFrequencyData(buffer)
    const average = buffer.reduce((sum, value) => sum + value, 0) / buffer.length
    waveform.value = [...waveform.value.slice(-39), average / 255]
    animationFrame = requestAnimationFrame(updateWaveform)
  }

  async function startRecording(): Promise<boolean> {
    if (isRecording.value || typeof navigator.mediaDevices?.getUserMedia !== 'function') {
      return false
    }

    cancelled.value = false
    waveform.value = []
    durationMs.value = 0
    chunks.length = 0

    try {
      mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true })
      const mimeType = pickMimeType()
      mediaRecorder = mimeType
        ? new MediaRecorder(mediaStream, { mimeType })
        : new MediaRecorder(mediaStream)

      audioContext = new AudioContext()
      const source = audioContext.createMediaStreamSource(mediaStream)
      analyser = audioContext.createAnalyser()
      analyser.fftSize = 256
      source.connect(analyser)

      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          chunks.push(event.data)
        }
      }

      mediaRecorder.start(100)
      isRecording.value = true
      startedAt = Date.now()
      updateWaveform()

      durationTimer = window.setInterval(() => {
        durationMs.value = Date.now() - startedAt
        if (durationMs.value >= MAX_DURATION_MS) {
          void stopRecording()
        }
      }, 100)

      return true
    } catch {
      cleanupStream()
      isRecording.value = false
      return false
    }
  }

  function cancelRecording() {
    cancelled.value = true
    cleanupStream()
    isRecording.value = false
    durationMs.value = 0
    waveform.value = []
  }

  async function stopRecording(): Promise<File | null> {
    if (!isRecording.value || !mediaRecorder) {
      return null
    }

    const recorder = mediaRecorder
    const mimeType = recorder.mimeType || 'audio/webm'
    const recordedDuration = durationMs.value

    const blob = await new Promise<Blob | null>((resolve) => {
      recorder.onstop = () => {
        if (chunks.length === 0) {
          resolve(null)
          return
        }
        resolve(new Blob(chunks, { type: mimeType }))
      }
      recorder.stop()
    })

    mediaRecorder = null
    mediaStream?.getTracks().forEach((track) => track.stop())
    mediaStream = null

    if (audioContext) {
      void audioContext.close()
      audioContext = null
    }

    analyser = null
    stopWaveform()
    stopDurationTimer()
    chunks.length = 0
    isRecording.value = false

    if (cancelled.value || !blob || blob.size === 0 || recordedDuration < 500) {
      durationMs.value = 0
      waveform.value = []
      return null
    }

    const extension = extensionForMime(mimeType)
    const file = new File([blob], `voice-${Date.now()}.${extension}`, { type: mimeType })
    durationMs.value = 0
    waveform.value = []
    return file
  }

  onBeforeUnmount(() => {
    cancelRecording()
  })

  return {
    isRecording,
    durationMs,
    waveform,
    cancelled,
    startRecording,
    stopRecording,
    cancelRecording,
  }
}
