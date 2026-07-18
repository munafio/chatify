type StopFn = () => void

let activeStopper: StopFn | null = null

export function setActiveVoice(stop: StopFn): void {
  if (activeStopper && activeStopper !== stop) {
    const previous = activeStopper
    activeStopper = null
    previous()
  }
  activeStopper = stop
}

export function clearActiveVoice(stop: StopFn): void {
  if (activeStopper === stop) {
    activeStopper = null
  }
}

export function stopAllVoicePlayback(): void {
  const current = activeStopper
  if (current) {
    activeStopper = null
    current()
  }
}
