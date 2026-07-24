<script setup lang="ts">
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import MessageStatusTicks from './MessageStatusTicks.vue'

defineProps<{
  status?: 'sending' | 'failed' | null
  read: boolean
  isOwn: boolean
}>()

const { t } = useChatifyI18n()
</script>

<template>
  <span
    v-if="status === 'sending'"
    class="chatify-message-status-sending"
    :title="t('ui.thread.delivery.sending')"
    :aria-label="t('ui.thread.delivery.sending_aria')"
  >
    <svg class="chatify:h-3.5 chatify:w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <circle cx="12" cy="12" r="9" stroke-width="2" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 2" />
    </svg>
  </span>
  <MessageStatusTicks v-else-if="isOwn && status !== 'failed'" :read="read" />
</template>
