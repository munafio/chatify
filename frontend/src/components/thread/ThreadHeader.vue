<script setup lang="ts">
import { computed } from 'vue'
import type { ChatifyConversation } from '../../types'
import { conversationAvatar, conversationDisplayName, isSavedConversation } from '../../utils/format'
import { displayUserAvatar, displayUserName, HIDDEN_USER_NAME } from '../../utils/userDisplay'
import { memberCountLabel } from '../../utils/group'
import { useUiStore } from '../../stores/ui'
import { useContactsStore } from '../../stores/contacts'
import { usePresenceStore } from '../../stores/presence'
import { useConfigStore } from '../../stores/config'
import { useConnectionStore } from '../../stores/connection'
import { storeToRefs } from 'pinia'
import { connectionStatusLabel } from '../../utils/connectionLabel'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  conversation: ChatifyConversation
  showBack?: boolean
}>()

defineEmits<{
  back: []
}>()

const uiStore = useUiStore()
const contactsStore = useContactsStore()
const presenceStore = usePresenceStore()
const configStore = useConfigStore()
const connectionStore = useConnectionStore()
const { defaultAvatarUrl, savedMessagesTitle } = storeToRefs(configStore)
const { messagingBlockedUserIds } = storeToRefs(contactsStore)
const { uiState } = storeToRefs(connectionStore)
const { t } = useChatifyI18n()

const isSaved = computed(() => isSavedConversation(props.conversation))

const otherUser = computed(() => props.conversation.relationships.other_user)

const displayName = computed(() => {
  messagingBlockedUserIds.value

  if (isSaved.value) {
    return conversationDisplayName(props.conversation, savedMessagesTitle.value)
  }

  if (props.conversation.attributes.conversation_type === 'group') {
    return props.conversation.attributes.name ?? t('ui.thread.header.group_default')
  }

  const user = otherUser.value
  if (!user) {
    return HIDDEN_USER_NAME
  }

  if (contactsStore.isMessagingBlocked(user.id) && !contactsStore.isBlockedByMe(user.id)) {
    return HIDDEN_USER_NAME
  }

  return displayUserName(user)
})

const avatarUrl = computed(() => {
  messagingBlockedUserIds.value

  if (isSaved.value) {
    return null
  }

  if (props.conversation.attributes.conversation_type === 'group') {
    return conversationAvatar(props.conversation, defaultAvatarUrl.value)
  }

  const user = otherUser.value
  if (!user) {
    return null
  }

  if (contactsStore.isMessagingBlocked(user.id) && !contactsStore.isBlockedByMe(user.id)) {
    return defaultAvatarUrl.value
  }

  return displayUserAvatar(user, defaultAvatarUrl.value) || null
})

const directOnlineLabel = computed(() => {
  if (props.conversation.attributes.conversation_type !== 'direct' || !otherUser.value) {
    return null
  }

  if (contactsStore.isMessagingBlocked(otherUser.value.id)) {
    return null
  }

  if (!presenceStore.canShowPresence()) {
    return t('ui.presence.tap_for_contact_info')
  }

  return presenceStore.visibleOnline(otherUser.value.id) ? t('ui.presence.online') : t('ui.presence.offline')
})

const connectionLabel = computed(() => connectionStatusLabel(uiState.value))

const threadSubtitle = computed(() => {
  if (connectionLabel.value) {
    return connectionLabel.value
  }

  if (isSaved.value) {
    return t('ui.presence.message_yourself')
  }

  if (props.conversation.attributes.conversation_type === 'group') {
    return memberCountLabel(props.conversation.attributes.participant_count)
  }

  return directOnlineLabel.value
})

const canOpenDirectInfo = computed(() => {
  if (isSaved.value) {
    return false
  }

  if (props.conversation.attributes.conversation_type !== 'direct' || !otherUser.value) {
    return false
  }

  return !contactsStore.isMessagingBlocked(otherUser.value.id)
})

function openInfo(conversation: ChatifyConversation) {
  if (conversation.attributes.conversation_type === 'group') {
    uiStore.openModal('groupInfo', { conversationId: conversation.id, view: 'main', membersMode: 'browse' })
    return
  }

  if (!canOpenDirectInfo.value) {
    return
  }

  uiStore.openModal('contactInfo', { user: conversation.relationships.other_user })
}
</script>

<template>
  <header class="chatify-sidebar-divide chatify:flex chatify:items-center chatify:gap-3 chatify:border-b chatify:bg-chatify-sidebar chatify:px-4 chatify:py-3">
    <button
      v-if="showBack"
      type="button"
      class="chatify:rounded-full chatify:p-2 chatify:hover:bg-chatify-border"
      :aria-label="$t('ui.thread.header.back')"
      @click="$emit('back')"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <button
      v-if="conversation.attributes.conversation_type === 'group' || canOpenDirectInfo"
      type="button"
      class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:cursor-pointer chatify:items-center chatify:gap-3 chatify:text-start"
      @click="openInfo(conversation)"
    >
      <div class="chatify:relative chatify:shrink-0">
        <div
          v-if="isSaved"
          class="chatify:flex chatify:h-10 chatify:w-10 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white"
        >
          <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
          </svg>
        </div>
        <img
          v-else-if="avatarUrl"
          :src="avatarUrl"
          :alt="displayName"
          class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover"
        />
        <span
          v-if="conversation.attributes.conversation_type === 'direct' && otherUser && !contactsStore.isMessagingBlocked(otherUser.id) && presenceStore.visibleOnline(otherUser.id)"
          class="chatify-presence-dot"
          :aria-label="$t('ui.thread.header.online')"
        />
      </div>
      <div
        v-if="!conversationAvatar(conversation, defaultAvatarUrl) && conversation.attributes.conversation_type === 'group'"
        class="chatify:flex chatify:h-10 chatify:w-10 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>

      <div class="chatify:min-w-0">
        <p class="chatify:truncate chatify:text-sm chatify:font-semibold">
          {{ displayName }}
        </p>
        <p
          v-if="threadSubtitle"
          class="chatify:text-xs chatify:text-chatify-muted"
          :class="{ 'chatify:animate-pulse': uiState !== 'online' }"
        >
          {{ threadSubtitle }}
        </p>
      </div>
    </button>

    <div
      v-else
      class="chatify:flex chatify:min-w-0 chatify:flex-1 chatify:items-center chatify:gap-3"
    >
      <div class="chatify:relative chatify:shrink-0">
        <div
          v-if="isSaved"
          class="chatify:flex chatify:h-10 chatify:w-10 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary-dark chatify:text-white"
        >
          <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z" />
          </svg>
        </div>
        <img
          v-else-if="avatarUrl"
          :src="avatarUrl"
          :alt="displayName"
          class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover"
        />
      </div>

      <div class="chatify:min-w-0">
        <p class="chatify:truncate chatify:text-sm chatify:font-semibold">
          {{ displayName }}
        </p>
        <p
          v-if="threadSubtitle"
          class="chatify:text-xs chatify:text-chatify-muted"
          :class="{ 'chatify:animate-pulse': uiState !== 'online' }"
        >
          {{ threadSubtitle }}
        </p>
      </div>
    </div>

    <button
      type="button"
      class="chatify:rounded-full chatify:p-2 chatify:hover:bg-chatify-border"
      :aria-label="$t('ui.thread.header.search')"
      @click="uiStore.openMessageSearch()"
    >
      <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
    </button>
  </header>
</template>
