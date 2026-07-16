<script setup lang="ts">
import type { ChatifyConversation } from '../../types'
import { conversationDisplayName, formatRelativeTime, truncate } from '../../utils/format'

defineProps<{
  conversation: ChatifyConversation
  active: boolean
}>()

defineEmits<{
  select: []
}>()
</script>

<template>
  <button
    type="button"
    class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:px-4 chatify:py-3 chatify:text-left"
    :class="{ 'chatify-list-item-active': active }"
    @click="$emit('select')"
  >
    <div class="chatify:relative chatify:h-12 chatify:w-12 chatify:shrink-0">
      <div class="chatify:flex chatify:h-12 chatify:w-12 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white">
        <svg class="chatify:h-6 chatify:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
    </div>

    <div class="chatify:min-w-0 chatify:flex-1">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2">
        <p class="chatify:truncate chatify:text-sm chatify:font-medium">
          {{ conversationDisplayName(conversation) }}
        </p>
        <span class="chatify:shrink-0 chatify:text-xs chatify:text-chatify-muted">
          {{ formatRelativeTime(conversation.relationships.last_message?.attributes.created_at) }}
        </span>
      </div>
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2">
        <p class="chatify:truncate chatify:text-xs chatify:text-chatify-muted">
          <span v-if="conversation.attributes.participant_count">
            {{ conversation.attributes.participant_count }} members ·
          </span>
          {{ truncate(conversation.relationships.last_message?.attributes.body ?? 'No messages yet') }}
        </p>
        <span
          v-if="conversation.attributes.unread_count > 0"
          class="chatify:flex chatify:h-5 chatify:min-w-5 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary chatify:px-1.5 chatify:text-[10px] chatify:font-semibold chatify:text-white"
        >
          {{ conversation.attributes.unread_count }}
        </span>
      </div>
    </div>
  </button>
</template>
