<script setup lang="ts">
import type { ChatifyConversation } from '../../types'
import { conversationAvatar, conversationDisplayName, formatRelativeTime, truncate } from '../../utils/format'

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
    <img
      v-if="conversationAvatar(conversation)"
      :src="conversationAvatar(conversation)!"
      :alt="conversationDisplayName(conversation)"
      class="chatify:h-12 chatify:w-12 chatify:shrink-0 chatify:rounded-full chatify:object-cover"
    />
    <div
      v-else
      class="chatify:flex chatify:h-12 chatify:w-12 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-border chatify:text-sm chatify:font-semibold"
    >
      {{ conversationDisplayName(conversation).charAt(0).toUpperCase() }}
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
