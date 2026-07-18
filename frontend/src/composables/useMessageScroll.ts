import { nextTick, onBeforeUnmount, ref, watch, type Ref } from 'vue'

const NEAR_BOTTOM_THRESHOLD = 120

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
  let boundEl: HTMLElement | null = null

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
    const near = distance <= NEAR_BOTTOM_THRESHOLD
    isNearBottom.value = near
    if (near) {
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
    updateNearBottom()

    if (isOwn) {
      options.pendingScrollToBottom.value = false
      void nextTick(() => scrollToBottom('smooth'))
      return
    }

    if (isNearBottom.value) {
      options.pendingScrollToBottom.value = false
      void nextTick(() => scrollToBottom('auto'))
      return
    }

    unseenCount.value += 1
  }

  watch(
    () => options.pendingScrollToBottom.value,
    (shouldScroll) => {
      if (!shouldScroll) {
        return
      }

      options.pendingScrollToBottom.value = false
      void nextTick(() => scrollToBottom('smooth'))
    },
  )

  function unbind() {
    if (boundEl) {
      boundEl.removeEventListener('scroll', onScroll)
      boundEl = null
    }
  }

  function bind() {
    const el = containerRef.value
    if (!el) {
      return
    }

    if (boundEl === el) {
      updateNearBottom()
      return
    }

    unbind()
    boundEl = el
    el.addEventListener('scroll', onScroll, { passive: true })
    updateNearBottom()
  }

  watch(
    containerRef,
    async (el) => {
      if (el) {
        await nextTick()
        bind()
      } else {
        unbind()
      }
    },
    { flush: 'post' },
  )

  onBeforeUnmount(() => {
    unbind()
  })

  return {
    isNearBottom,
    unseenCount,
    scrollToBottom,
    notifyNewMessage,
    bind,
    updateNearBottom,
  }
}
