import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastPlacement = 'top' | 'bottom'
export type ToastIcon = 'none' | 'error' | 'success' | 'info'

export interface ToastOptions {
  message: string
  placement?: ToastPlacement
  icon?: ToastIcon
  duration?: number
}

export interface ToastItem extends Required<Pick<ToastOptions, 'message'>> {
  id: number
  placement: ToastPlacement
  icon: ToastIcon
  duration: number
}

let nextId = 1

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<ToastItem[]>([])

  function show(options: ToastOptions) {
    const toast: ToastItem = {
      id: nextId++,
      message: options.message,
      placement: options.placement ?? 'bottom',
      icon: options.icon ?? 'none',
      duration: options.duration ?? 3200,
    }

    toasts.value = [...toasts.value, toast]

    window.setTimeout(() => {
      dismiss(toast.id)
    }, toast.duration)

    return toast.id
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id)
  }

  return {
    toasts,
    show,
    dismiss,
  }
})
