import { onBeforeUnmount, ref, type Ref } from 'vue'

export type ComposerDropKind = 'media' | 'document' | 'mixed'

export interface ComposerDropPayload {
  files: File[]
  kind: ComposerDropKind
}

function classifyFiles(files: File[]): ComposerDropKind {
  if (files.length === 0) {
    return 'mixed'
  }

  const hasMedia = files.some((file) => file.type.startsWith('image/') || file.type.startsWith('video/'))
  const hasDocument = files.some((file) => !file.type.startsWith('image/') && !file.type.startsWith('video/'))

  if (hasMedia && hasDocument) {
    return 'mixed'
  }
  if (hasMedia) {
    return 'media'
  }
  if (hasDocument) {
    return 'document'
  }
  return 'mixed'
}

function extractFiles(dataTransfer: DataTransfer | null): File[] {
  if (!dataTransfer?.files?.length) {
    return []
  }

  return Array.from(dataTransfer.files)
}

export function useComposerDrop(
  containerRef: Ref<HTMLElement | null>,
  onDrop: (payload: ComposerDropPayload) => void,
) {
  const isDragging = ref(false)
  const dropKind = ref<ComposerDropKind | null>(null)
  let dragDepth = 0

  function resetDragState() {
    dragDepth = 0
    isDragging.value = false
    dropKind.value = null
  }

  function onDragEnter(event: DragEvent) {
    if (!event.dataTransfer?.types.includes('Files')) {
      return
    }

    event.preventDefault()
    dragDepth += 1
    isDragging.value = true

    const files = extractFiles(event.dataTransfer)
    dropKind.value = files.length > 0 ? classifyFiles(files) : 'media'
  }

  function onDragOver(event: DragEvent) {
    if (!event.dataTransfer?.types.includes('Files')) {
      return
    }

    event.preventDefault()
    event.dataTransfer.dropEffect = 'copy'

    const files = extractFiles(event.dataTransfer)
    if (files.length > 0) {
      dropKind.value = classifyFiles(files)
    }
  }

  function onDragLeave(event: DragEvent) {
    if (!event.dataTransfer?.types.includes('Files')) {
      return
    }

    event.preventDefault()
    dragDepth = Math.max(0, dragDepth - 1)
    if (dragDepth === 0) {
      isDragging.value = false
      dropKind.value = null
    }
  }

  function onDropEvent(event: DragEvent) {
    if (!event.dataTransfer?.types.includes('Files')) {
      return
    }

    event.preventDefault()
    const files = extractFiles(event.dataTransfer)
    resetDragState()

    if (files.length === 0) {
      return
    }

    onDrop({
      files,
      kind: classifyFiles(files),
    })
  }

  function bind() {
    const el = containerRef.value
    if (!el) {
      return
    }

    el.addEventListener('dragenter', onDragEnter)
    el.addEventListener('dragover', onDragOver)
    el.addEventListener('dragleave', onDragLeave)
    el.addEventListener('drop', onDropEvent)
  }

  function unbind() {
    const el = containerRef.value
    if (!el) {
      return
    }

    el.removeEventListener('dragenter', onDragEnter)
    el.removeEventListener('dragover', onDragOver)
    el.removeEventListener('dragleave', onDragLeave)
    el.removeEventListener('drop', onDropEvent)
    resetDragState()
  }

  onBeforeUnmount(unbind)

  return {
    isDragging,
    dropKind,
    bind,
    unbind,
  }
}
