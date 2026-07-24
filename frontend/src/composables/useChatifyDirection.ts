import { computed } from 'vue'
import { useConfigStore } from '../stores/config'

export function useChatifyDirection() {
  const configStore = useConfigStore()

  const dir = computed(() => configStore.dir)
  const isRtl = computed(() => dir.value === 'rtl')

  return { dir, isRtl }
}
