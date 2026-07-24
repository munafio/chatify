import { onMounted, onUnmounted, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useBreakpoints } from './useBreakpoints'
import { useConfigStore } from '../stores/config'
import { useConversationsStore } from '../stores/conversations'
import { useUiStore } from '../stores/ui'
import {
  buildConversationPath,
  currentConversationPath,
  parseConversationIdFromLocation,
} from '../utils/conversationUrl'

export function useConversationRouting() {
  const configStore = useConfigStore()
  const conversationsStore = useConversationsStore()
  const uiStore = useUiStore()
  const { isMobile } = useBreakpoints()
  const { activeId } = storeToRefs(conversationsStore)

  let applyingFromHistory = false

  function webBase(): string {
    return configStore.boot?.webBase ?? '/chatify'
  }

  function syncStoreFromUrl() {
    const conversationId = parseConversationIdFromLocation(webBase())

    applyingFromHistory = true

    if (conversationId) {
      void conversationsStore.select(conversationId).then((selected) => {
        if (!selected) {
          navigateToInbox(true)
        } else if (isMobile.value) {
          uiStore.showThread()
        }
        applyingFromHistory = false
      })
      return
    }

    conversationsStore.clearActive()
    uiStore.hideThread()
    applyingFromHistory = false
  }

  function syncUrlFromStore(replace = false) {
    if (applyingFromHistory || typeof window === 'undefined') {
      return
    }

    const targetPath = buildConversationPath(webBase(), activeId.value)
    const currentPath = currentConversationPath(webBase())

    if (targetPath === currentPath) {
      return
    }

    applyingFromHistory = true
    const method = replace ? 'replaceState' : 'pushState'
    window.history[method](null, '', targetPath)
    applyingFromHistory = false
  }

  function onPopState() {
    syncStoreFromUrl()
  }

  function navigateToInbox(replace = false) {
    if (typeof window === 'undefined') {
      conversationsStore.clearActive()
      uiStore.hideThread()
      return
    }

    applyingFromHistory = true
    const targetPath = buildConversationPath(webBase(), null)
    const method = replace ? 'replaceState' : 'pushState'
    window.history[method](null, '', targetPath)
    applyingFromHistory = false
    conversationsStore.clearActive()
    uiStore.hideThread()
  }

  function goBack() {
    if (typeof window === 'undefined') {
      navigateToInbox()
      return
    }

    const hasConversationInUrl = parseConversationIdFromLocation(webBase()) !== null
    if (hasConversationInUrl && window.history.length > 1) {
      window.history.back()
      return
    }

    navigateToInbox(true)
  }

  onMounted(() => {
    window.addEventListener('popstate', onPopState)

    watch(
      activeId,
      () => {
        syncUrlFromStore(false)
      },
      { flush: 'post' },
    )

    const bootConversationId = configStore.boot?.conversationId ?? null
    if (bootConversationId && bootConversationId === activeId.value) {
      syncUrlFromStore(true)
    }
  })

  onUnmounted(() => {
    window.removeEventListener('popstate', onPopState)
  })

  return {
    goBack,
    navigateToInbox,
  }
}
