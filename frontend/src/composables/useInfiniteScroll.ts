import { onBeforeUnmount, onMounted, ref, type Ref } from 'vue'

export function useInfiniteScroll(loadMore: () => Promise<void> | void, options?: { root?: Ref<HTMLElement | null> }) {
  const sentinel = ref<HTMLElement | null>(null)
  const loading = ref(false)
  let observer: IntersectionObserver | null = null

  async function handleIntersect(entries: IntersectionObserverEntry[]) {
    if (!entries.some((entry) => entry.isIntersecting) || loading.value) {
      return
    }

    loading.value = true
    try {
      await loadMore()
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    if (!sentinel.value) {
      return
    }

    observer = new IntersectionObserver(handleIntersect, {
      root: options?.root?.value ?? null,
      rootMargin: '120px',
    })
    observer.observe(sentinel.value)
  })

  onBeforeUnmount(() => {
    observer?.disconnect()
  })

  return { sentinel, loading }
}
