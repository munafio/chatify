import { onMounted, onUnmounted, ref } from 'vue'

const MOBILE_BREAKPOINT = 768

export function useBreakpoints() {
  const isMobile = ref(false)

  function update() {
    isMobile.value = window.innerWidth < MOBILE_BREAKPOINT
  }

  onMounted(() => {
    update()
    window.addEventListener('resize', update)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', update)
  })

  return { isMobile }
}
