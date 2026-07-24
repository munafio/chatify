<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyConversation } from '../../types'
import { conversationAvatar, conversationDisplayName, formatRelativeTime, truncate } from '../../utils/format'
import { memberCountLabel } from '../../utils/group'
import { useConfigStore } from '../../stores/config'
import ConversationTypeIcon from '../ui/ConversationTypeIcon.vue'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  conversation: ChatifyConversation
  active: boolean
  pinned?: boolean
  draggable?: boolean
}>()

defineEmits<{
  select: []
}>()

const configStore = useConfigStore()
const { defaultAvatarUrl } = storeToRefs(configStore)
const { t } = useChatifyI18n()

const displayName = computed(() => conversationDisplayName(props.conversation))

const avatarUrl = computed(() =>
  conversationAvatar(props.conversation, defaultAvatarUrl.value),
)
</script>

<template>
  <button
    type="button"
    class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:px-4 chatify:py-3 chatify:text-start"
    :class="{ 'chatify-list-item-active': active }"
    @click="$emit('select')"
  >
    <div class="chatify:relative chatify:h-12 chatify:w-12 chatify:shrink-0">
      <img
        v-if="avatarUrl"
        :src="avatarUrl"
        :alt="displayName"
        class="chatify:h-full chatify:w-full chatify:rounded-full chatify:object-cover"
      />
      <div
        v-else
        class="chatify:flex chatify:h-12 chatify:w-12 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white"
      >
        <svg class="chatify:h-6 chatify:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
    </div>

    <div class="chatify:min-w-0 chatify:flex-1">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2">
        <div class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:items-center chatify:gap-1">
          <ConversationTypeIcon kind="group" />
          <p class="chatify:min-w-0 chatify:flex-1 chatify:truncate chatify:text-sm chatify:font-medium">
            {{ displayName }}
          </p>
          <svg
            v-if="pinned"
            class="chatify:h-3.5 chatify:w-3.5 chatify:shrink-0 chatify:text-chatify-muted"
            fill="currentColor"
            viewBox="0 0 24 24"
            :aria-label="$t('ui.sidebar.pinned')"
          >
            <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z" />
          </svg>
        </div>
        <div class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-1">
          <div
            v-if="draggable"
            class="chatify-conversation-drag-handle chatify:flex chatify:h-6 chatify:w-5 chatify:cursor-grab chatify:items-center chatify:justify-center chatify:rounded chatify:text-chatify-muted chatify:opacity-60 hover:chatify:opacity-100"
            :aria-label="$t('ui.sidebar.drag_to_reorder')"
            @click.stop
          >
            <svg class="chatify:h-4 chatify:w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="9" cy="7" r="1.5" />
              <circle cx="15" cy="7" r="1.5" />
              <circle cx="9" cy="12" r="1.5" />
              <circle cx="15" cy="12" r="1.5" />
              <circle cx="9" cy="17" r="1.5" />
              <circle cx="15" cy="17" r="1.5" />
            </svg>
          </div>
          <span class="chatify:text-xs chatify:text-chatify-muted">
            {{ formatRelativeTime(conversation.relationships.last_message?.attributes.created_at) }}
          </span>
        </div>
      </div>
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2">
        <p class="chatify:truncate chatify:text-xs chatify:text-chatify-muted">
          <span v-if="conversation.attributes.participant_count">
            {{ memberCountLabel(conversation.attributes.participant_count) }} ·
          </span>
          {{ truncate(conversation.relationships.last_message?.attributes.body ?? t('ui.sidebar.no_messages_yet')) }}
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
