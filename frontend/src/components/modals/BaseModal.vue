<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  title: string
  open?: boolean
  size?: 'sm' | 'md' | 'lg'
  bare?: boolean
  panelClass?: string
}>()

const emit = defineEmits<{
  close: []
}>()

const { t } = useChatifyI18n()

function onKeydown(event: KeyboardEvent) {
  if (event.key !== 'Escape' || !props.open) {
    return
  }

  event.preventDefault()
  emit('close')
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      document.addEventListener('keydown', onKeydown)
      return
    }

    document.removeEventListener('keydown', onKeydown)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport :to="CHATIFY_TELEPORT_TARGET">
    <Transition name="chatify-modal">
      <div
        v-if="open"
        class="chatify:fixed chatify:inset-0 chatify:z-50 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/40 chatify:p-4"
        @click.self="$emit('close')"
      >
        <div
          role="dialog"
          aria-modal="true"
          :aria-label="title"
          class="chatify-modal-panel chatify:flex chatify:max-h-[min(90dvh,720px)] chatify:w-full chatify:flex-col chatify:overflow-hidden chatify:rounded-xl chatify:bg-chatify-bubble-in chatify:text-chatify-text chatify:shadow-xl"
          :class="[
            panelClass,
            {
              'chatify:max-w-sm': size === 'sm',
              'chatify:max-w-md': !size || size === 'md',
              'chatify:max-w-lg': size === 'lg',
            },
          ]"
        >
          <header
            v-if="!bare"
            class="chatify:flex chatify:items-center chatify:justify-between chatify:border-b chatify:border-chatify-border chatify:px-4 chatify:py-3"
          >
            <h2 class="chatify:text-base chatify:font-semibold chatify:text-chatify-text">{{ title }}</h2>
            <button
              type="button"
              class="chatify:rounded-full chatify:p-1 chatify:hover:bg-chatify-border"
              :aria-label="t('ui.modals.close')"
              @click="$emit('close')"
            >
              <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </header>
          <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:overflow-hidden" :class="bare ? '' : 'chatify:overflow-y-auto chatify:p-4'">
            <slot />
          </div>
          <footer v-if="$slots.footer && !bare" class="chatify:border-t chatify:border-chatify-border chatify:p-4">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
