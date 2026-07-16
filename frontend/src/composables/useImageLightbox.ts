import { ref } from 'vue'
import type { MessageAttachment } from '../types'

const open = ref(false)
const images = ref<MessageAttachment[]>([])
const activeIndex = ref(0)

export function useImageLightbox() {
  function show(items: MessageAttachment[], startIndex = 0) {
    images.value = items
    activeIndex.value = Math.max(0, Math.min(startIndex, items.length - 1))
    open.value = true
  }

  function close() {
    open.value = false
  }

  function next() {
    if (images.value.length === 0) {
      return
    }
    activeIndex.value = (activeIndex.value + 1) % images.value.length
  }

  function prev() {
    if (images.value.length === 0) {
      return
    }
    activeIndex.value = (activeIndex.value - 1 + images.value.length) % images.value.length
  }

  return {
    open,
    images,
    activeIndex,
    show,
    close,
    next,
    prev,
  }
}
