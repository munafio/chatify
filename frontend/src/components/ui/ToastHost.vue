<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'
import { useToastStore, type ToastIcon, type ToastPlacement } from '../../stores/toast'

const toastStore = useToastStore()
const { toasts } = storeToRefs(toastStore)

const grouped = computed(() => ({
  top: toasts.value.filter((toast) => toast.placement === 'top'),
  bottom: toasts.value.filter((toast) => toast.placement === 'bottom'),
}))

function iconPath(icon: ToastIcon): string | null {
  switch (icon) {
    case 'error':
      return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    case 'success':
      return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    case 'info':
      return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    default:
      return null
  }
}
</script>

<template>
  <Teleport :to="CHATIFY_TELEPORT_TARGET">
    <div
      v-for="placement in (['top', 'bottom'] as ToastPlacement[])"
      :key="placement"
      class="chatify-toast-host"
      :class="placement === 'top' ? 'chatify-toast-host-top' : 'chatify-toast-host-bottom'"
    >
      <TransitionGroup name="chatify-toast">
        <div
          v-for="toast in grouped[placement]"
          :key="toast.id"
          class="chatify-toast"
          role="status"
          aria-live="polite"
        >
          <svg
            v-if="iconPath(toast.icon)"
            class="chatify-toast-icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath(toast.icon)!" />
          </svg>
          <span class="chatify-toast-message">{{ toast.message }}</span>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>
