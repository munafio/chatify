<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyConversation } from '../../types'
import { conversationAvatar, conversationDisplayName, formatRelativeTime, isSavedConversation, truncate } from '../../utils/format'
import { displayUserAvatar, displayUserName, HIDDEN_USER_NAME } from '../../utils/userDisplay'
import { useContactsStore } from '../../stores/contacts'
import { usePresenceStore } from '../../stores/presence'
import { useConfigStore } from '../../stores/config'

const props = defineProps<{
  conversation: ChatifyConversation
  active: boolean
  pinned?: boolean
  draggable?: boolean
}>()

defineEmits<{
  select: []
}>()

const presenceStore = usePresenceStore()
const contactsStore = useContactsStore()
const configStore = useConfigStore()
const { defaultAvatarUrl, savedMessagesTitle } = storeToRefs(configStore)
const { messagingBlockedUserIds } = storeToRefs(contactsStore)

const isSaved = computed(() => isSavedConversation(props.conversation))

const otherUserId = computed(() => props.conversation.relationships.other_user?.id ?? null)

const displayName = computed(() => {
  messagingBlockedUserIds.value

  if (isSaved.value) {
    return conversationDisplayName(props.conversation, savedMessagesTitle.value)
  }

  if (props.conversation.attributes.conversation_type === 'group') {
    return props.conversation.attributes.name ?? 'Group'
  }

  const otherUser = props.conversation.relationships.other_user
  if (!otherUser) {
    return HIDDEN_USER_NAME
  }

  if (contactsStore.isMessagingBlocked(otherUser.id) && !contactsStore.isBlockedByMe(otherUser.id)) {
    return HIDDEN_USER_NAME
  }

  return displayUserName(otherUser)
})

const avatarUrl = computed(() => {
  messagingBlockedUserIds.value

  if (isSaved.value) {
    return null
  }

  if (props.conversation.attributes.conversation_type === 'group') {
    return conversationAvatar(props.conversation, defaultAvatarUrl.value)
  }

  const otherUser = props.conversation.relationships.other_user
  if (!otherUser) {
    return null
  }

  if (contactsStore.isMessagingBlocked(otherUser.id) && !contactsStore.isBlockedByMe(otherUser.id)) {
    return defaultAvatarUrl.value
  }

  return displayUserAvatar(otherUser, defaultAvatarUrl.value) || null
})

const showPresenceDot = computed(() =>
  !isSaved.value
  && otherUserId.value !== null
  && !contactsStore.isMessagingBlocked(otherUserId.value)
  && presenceStore.visibleOnline(otherUserId.value),
)

const showBlockedIcon = computed(() =>
  !isSaved.value
  && otherUserId.value !== null
  && contactsStore.isMessagingBlocked(otherUserId.value),
)
</script>

<template>
  <button
    type="button"
    class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:px-4 chatify:py-3 chatify:text-left"
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
        v-else-if="isSaved"
        class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white"
      >
        <svg class="chatify:h-6 chatify:w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
        </svg>
      </div>
      <div
        v-else
        class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-border chatify:text-sm chatify:font-semibold"
      >
        {{ displayName.charAt(0).toUpperCase() }}
      </div>
      <span
        v-if="showPresenceDot"
        class="chatify-presence-dot"
        aria-label="Online"
      />
    </div>

    <div class="chatify:min-w-0 chatify:flex-1">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:gap-2">
        <div class="chatify:flex chatify:min-w-0 chatify:items-center chatify:gap-1.5">
          <p class="chatify:truncate chatify:text-sm chatify:font-medium">
            {{ displayName }}
          </p>
          <svg
            v-if="showBlockedIcon"
            class="chatify:h-3.5 chatify:w-3.5 chatify:shrink-0 chatify:text-chatify-muted"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-label="Blocked"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          <svg
            v-if="pinned"
            class="chatify:h-3.5 chatify:w-3.5 chatify:shrink-0 chatify:text-chatify-muted"
            fill="currentColor"
            viewBox="0 0 24 24"
            aria-label="Pinned"
          >
            <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z" />
          </svg>
        </div>
        <div class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-1">
          <div
            v-if="draggable"
            class="chatify-conversation-drag-handle chatify:flex chatify:h-6 chatify:w-5 chatify:cursor-grab chatify:items-center chatify:justify-center chatify:rounded chatify:text-chatify-muted chatify:opacity-60 hover:chatify:opacity-100"
            aria-label="Drag to reorder"
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
