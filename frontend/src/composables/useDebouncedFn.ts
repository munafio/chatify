import { onUnmounted, watch, type Ref } from 'vue'

export function useDebouncedWatch(
  source: Ref<string>,
  fn: (value: string) => void | Promise<void>,
  delay = 300,
): void {
  let timer: ReturnType<typeof setTimeout> | undefined

  watch(source, (value) => {
    if (timer) {
      clearTimeout(timer)
    }

    const trimmed = value.trim()

    if (!trimmed) {
      void fn('')
      return
    }

    timer = setTimeout(() => {
      void fn(trimmed)
    }, delay)
  })

  onUnmounted(() => {
    if (timer) {
      clearTimeout(timer)
    }
  })
}
