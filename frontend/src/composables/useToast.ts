import { useToastStore, type ToastOptions } from '../stores/toast'

export function useToast() {
  const toastStore = useToastStore()

  return {
    show: (options: ToastOptions) => toastStore.show(options),
    dismiss: (id: number) => toastStore.dismiss(id),
  }
}

export type { ToastIcon, ToastOptions, ToastPlacement } from '../stores/toast'
