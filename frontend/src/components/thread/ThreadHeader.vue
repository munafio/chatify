<script setup lang="ts">
import type { ChatifyConversation } from '../../types'
import { conversationAvatar, conversationDisplayName } from '../../utils/format'
import { memberCountLabel } from '../../utils/group'
import { useUiStore } from '../../stores/ui'

defineProps<{
  conversation: ChatifyConversation
  showBack?: boolean
}>()

defineEmits<{
  back: []
}>()

const uiStore = useUiStore()

function openInfo(conversation: ChatifyConversation) {
  if (conversation.attributes.conversation_type === 'group') {
    uiStore.openModal('groupInfo', { conversationId: conversation.id, view: 'main', membersMode: 'browse' })
  } else {
    uiStore.openModal('contactInfo', { user: conversation.relationships.other_user })
  }
}
</script>

<template>
  <header class="chatify-sidebar-divide chatify:flex chatify:items-center chatify:gap-3 chatify:border-b chatify:bg-chatify-sidebar chatify:px-4 chatify:py-3">
    <button
      v-if="showBack"
      type="button"
      class="chatify:rounded-full chatify:p-2 chatify:hover:bg-chatify-border"
      aria-label="Back"
      @click="$emit('back')"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <button
      type="button"
      class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:cursor-pointer chatify:items-center chatify:gap-3 chatify:text-left"
      @click="openInfo(conversation)"
    >
      <img
        v-if="conversationAvatar(conversation)"
        :src="conversationAvatar(conversation)!"
        :alt="conversationDisplayName(conversation)"
        class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover"
      />
      <div
        v-else-if="conversation.attributes.conversation_type === 'group'"
        class="chatify:flex chatify:h-10 chatify:w-10 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>

      <div class="chatify:min-w-0">
        <p class="chatify:truncate chatify:text-sm chatify:font-semibold">
          {{ conversationDisplayName(conversation) }}
        </p>
        <p v-if="conversation.attributes.conversation_type === 'group'" class="chatify:text-xs chatify:text-chatify-muted">
          {{ memberCountLabel(conversation.attributes.participant_count) }}
        </p>
        <p v-else class="chatify:text-xs chatify:text-chatify-muted">
          tap for contact info
        </p>
      </div>
    </button>

    <button
      v-if="conversation.attributes.conversation_type === 'group'"
      type="button"
      class="chatify:rounded-full chatify:p-2 chatify:hover:bg-chatify-border"
      aria-label="Search conversation"
      @click="uiStore.openMessageSearch()"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
    </button>
  </header>
</template>
