<script setup lang="ts">
import { computed } from 'vue'
import {
  conversationTypeIconLabelKey,
  type ConversationTypeIconKind,
} from '../../utils/conversationTypeIcon'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  kind: ConversationTypeIconKind
}>()

const { t } = useChatifyI18n()

const label = computed(() => t(conversationTypeIconLabelKey(props.kind)))
</script>

<template>
  <span
    class="chatify-conversation-type-icon"
    role="img"
    :aria-label="label"
  >
    <svg
      v-if="kind === 'group'"
      class="chatify-conversation-type-icon-svg"
      fill="currentColor"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
    </svg>
    <svg
      v-else-if="kind === 'saved'"
      class="chatify-conversation-type-icon-svg"
      fill="currentColor"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
    </svg>
  </span>
</template>
