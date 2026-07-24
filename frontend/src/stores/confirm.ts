import { defineStore } from 'pinia'
import { ref } from 'vue'
import { chatifyT } from '../i18n/nonComponent'

export type ConfirmVariant = 'default' | 'danger'

export interface ConfirmOptions {
  title: string
  message?: string
  confirmLabel?: string
  cancelLabel?: string
  variant?: ConfirmVariant
}

export const useConfirmStore = defineStore('confirm', () => {
  const options = ref<ConfirmOptions | null>(null)
  let resolver: ((value: boolean) => void) | null = null

  function confirm(payload: ConfirmOptions): Promise<boolean> {
    if (options.value) {
      answer(false)
    }

    options.value = {
      confirmLabel: chatifyT('ui.common.confirm'),
      cancelLabel: chatifyT('ui.common.cancel'),
      variant: 'default',
      ...payload,
    }

    return new Promise<boolean>((resolve) => {
      resolver = resolve
    })
  }

  function answer(value: boolean) {
    resolver?.(value)
    resolver = null
    options.value = null
  }

  return {
    options,
    confirm,
    answer,
  }
})
