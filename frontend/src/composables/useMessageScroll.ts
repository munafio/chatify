import { nextTick, onBeforeUnmount, ref, watch, type Ref } from 'vue'

const NEAR_BOTTOM_THRESHOLD = 80

export function useMessageScroll(
  containerRef: Ref<HTMLElement | null>,
  options: {
    onLoadOlder: () => void | Promise<void>
    pendingScrollToBottom: Ref<boolean>
  },
) {
  const isNearBottom = ref(true)
  const unseenCount = ref(0)
  let loadingOlderGuard = false

  function scrollToBottom(behavior: ScrollBehavior = 'auto') {
    const el = containerRef.value
    if (!el) {
      return
    }

    el.scrollTo({ top: el.scrollHeight, behavior })
    isNearBottom.value = true
    unseenCount.value = 0
  }

  function updateNearBottom() {
    const el = containerRef.value
    if (!el) {
      return
    }

    const distance = el.scrollHeight - el.scrollTop - el.clientHeight
    isNearBottom.value = distance <= NEAR_BOTTOM_THRESHOLD
    if (isNearBottom.value) {
      unseenCount.value = 0
    }
  }

  async function onScroll() {
    const el = containerRef.value
    if (!el) {
      return
    }

    updateNearBottom()

    if (el.scrollTop <= 48 && !loadingOlderGuard) {
      loadingOlderGuard = true
      try {
        await options.onLoadOlder()
      } finally {
        loadingOlderGuard = false
      }
    }
  }

  function notifyNewMessage(isOwn: boolean) {
    if (isOwn || isNearBottom.value || options.pendingScrollToBottom.value) {
      options.pendingScrollToBottom.value = false
      void nextTick(() => scrollToBottom(isOwn ? 'smooth' : 'auto'))
      return
    }

    unseenCount.value += 1
  }

  watch(
    () => options.pendingScrollToBottom.value,
    (shouldScroll) => {
      if (shouldScroll) {
        options.pendingScrollToBottom.value = false
        void nextTick(() => scrollToBottom('smooth'))
      }
    },
  )

  onBeforeUnmount(() => {
    containerRef.value?.removeEventListener('scroll', onScroll)
  })

  function bind() {
    const el = containerRef.value
    if (!el) {
      return
    }

    el.removeEventListener('scroll', onScroll)
    el.addEventListener('scroll', onScroll, { passive: true })
    updateNearBottom()
  }

  return {
    isNearBottom,
    unseenCount,
    scrollToBottom,
    notifyNewMessage,
    bind,
    updateNearBottom,
  }
}
