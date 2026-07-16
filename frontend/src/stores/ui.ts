import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ModalName } from '../types'

export const useUiStore = defineStore('ui', () => {
  const activeModal = ref<ModalName>(null)
  const modalContext = ref<Record<string, unknown>>({})
  const showThreadOnMobile = ref(false)
  const composerDraft = ref('')

  function openModal(name: Exclude<ModalName, null>, context: Record<string, unknown> = {}) {
    activeModal.value = name
    modalContext.value = context
  }

  function closeModal() {
    activeModal.value = null
    modalContext.value = {}
  }

  function showThread() {
    showThreadOnMobile.value = true
  }

  function hideThread() {
    showThreadOnMobile.value = false
  }

  function setComposerDraft(value: string) {
    composerDraft.value = value
  }

  return {
    activeModal,
    modalContext,
    showThreadOnMobile,
    composerDraft,
    openModal,
    closeModal,
    showThread,
    hideThread,
    setComposerDraft,
  }
})
