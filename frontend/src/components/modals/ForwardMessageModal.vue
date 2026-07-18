<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useConversationsStore } from '../../stores/conversations'
import { useMessagesStore } from '../../stores/messages'
import { useUiStore } from '../../stores/ui'
import { useContactsStore } from '../../stores/contacts'
import { useConfigStore } from '../../stores/config'
import { conversationAvatar, conversationDisplayName } from '../../utils/format'
import BaseModal from './BaseModal.vue'

const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const messagesStore = useMessagesStore()
const contactsStore = useContactsStore()
const configStore = useConfigStore()
const { activeModal } = storeToRefs(uiStore)
const { forwardMessage } = storeToRefs(messagesStore)
const { filteredItems } = storeToRefs(conversationsStore)
const { defaultAvatarUrl } = storeToRefs(configStore)

const search = ref('')
const forwarding = ref(false)

const open = computed(() => activeModal.value === 'forwardMessage')

const targets = computed(() => {
  const q = search.value.trim().toLowerCase()
  return filteredItems.value.filter((conversation) => {
    if (conversation.id === conversationsStore.activeId) {
      return false
    }

    if (
      conversation.attributes.conversation_type === 'direct'
      && conversation.relationships.other_user
      && contactsStore.isBlockedByMe(conversation.relationships.other_user.id)
    ) {
      return false
    }

    if (!q) {
      return true
    }

    return conversationDisplayName(conversation).toLowerCase().includes(q)
  })
})

watch(open, (isOpen) => {
  if (!isOpen) {
    search.value = ''
    messagesStore.setForwardMessage(null)
  }
})

function displayName(conversation: (typeof filteredItems.value)[number]) {
  return conversationDisplayName(conversation)
}

function avatar(conversation: (typeof filteredItems.value)[number]) {
  return conversationAvatar(conversation, defaultAvatarUrl.value)
}

async function forwardTo(conversationId: string) {
  if (!forwardMessage.value) {
    return
  }

  forwarding.value = true
  try {
    await messagesStore.forwardMessageTo(conversationId, forwardMessage.value)
    uiStore.closeModal()
  } finally {
    forwarding.value = false
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Forward message"
    size="md"
    @close="uiStore.closeModal()"
  >
    <div class="chatify:space-y-4">
      <input
        v-model="search"
        type="search"
        placeholder="Search conversations"
        class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-2 chatify:text-sm chatify:focus:outline-none chatify:focus:ring-2 chatify:focus:ring-chatify-primary"
      />

      <p v-if="targets.length === 0" class="chatify:text-sm chatify:text-chatify-muted">
        No conversations available to forward to.
      </p>

      <ul
        v-else
        class="chatify:max-h-64 chatify:overflow-y-auto chatify:rounded-lg chatify:border chatify:border-chatify-border"
      >
        <li
          v-for="conversation in targets"
          :key="conversation.id"
          class="chatify:flex chatify:items-center chatify:gap-3 chatify:px-3 chatify:py-2 chatify:hover:bg-chatify-sidebar"
        >
          <img
            v-if="avatar(conversation)"
            :src="avatar(conversation)!"
            :alt="displayName(conversation)"
            class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover"
          />
          <div
            v-else
            class="chatify:flex chatify:h-10 chatify:w-10 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary chatify:text-sm chatify:font-semibold chatify:text-white"
          >
            {{ displayName(conversation).slice(0, 1).toUpperCase() }}
          </div>
          <div class="chatify:min-w-0 chatify:flex-1">
            <p class="chatify:truncate chatify:text-sm chatify:font-medium">{{ displayName(conversation) }}</p>
          </div>
          <button
            type="button"
            class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-white chatify:disabled:opacity-50"
            :disabled="forwarding"
            @click="forwardTo(conversation.id)"
          >
            Forward
          </button>
        </li>
      </ul>
    </div>
  </BaseModal>
</template>
