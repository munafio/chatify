<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useDebouncedWatch } from '../../composables/useDebouncedFn'
import { useConfigStore } from '../../stores/config'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import BaseModal from './BaseModal.vue'

const uiStore = useUiStore()
const configStore = useConfigStore()
const contactsStore = useContactsStore()
const conversationsStore = useConversationsStore()
const { activeModal, modalContext } = storeToRefs(uiStore)

const search = ref('')
const adding = ref(false)

const open = computed(() => activeModal.value === 'groupInfo')
const conversation = computed(() =>
  conversationsStore.items.find((item) => item.id === modalContext.value.conversationId) ?? null,
)

useDebouncedWatch(search, (query) => contactsStore.search(query))

watch(open, (isOpen) => {
  if (!isOpen) {
    search.value = ''
    contactsStore.searchResults = []
  }
})

async function addParticipant(userId: number | string) {
  if (!conversation.value || !configStore.api) {
    return
  }

  adding.value = true
  try {
    const { data } = await configStore.api.addParticipants(conversation.value.id, [userId])
    conversationsStore.upsert(data.data)
    search.value = ''
    contactsStore.searchResults = []
  } finally {
    adding.value = false
  }
}

async function removeParticipant(userId: number | string) {
  if (!conversation.value || !configStore.api) {
    return
  }

  await configStore.api.removeParticipant(conversation.value.id, userId)
  await conversationsStore.select(conversation.value.id)
}

async function leaveGroup() {
  if (!conversation.value || !configStore.api) {
    return
  }

  await configStore.api.leaveGroup(conversation.value.id)
  uiStore.closeModal()
  conversationsStore.clearActive()
  await conversationsStore.fetchAll()
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Group info"
    size="lg"
    @close="uiStore.closeModal()"
  >
    <div v-if="conversation" class="chatify:space-y-4">
      <div>
        <p class="chatify:text-lg chatify:font-semibold">{{ conversation.attributes.name }}</p>
        <p class="chatify:text-sm chatify:text-chatify-muted">
          {{ conversation.attributes.participant_count }} participants
        </p>
      </div>

      <ul class="chatify:divide-y chatify:divide-chatify-border chatify:rounded-lg chatify:border chatify:border-chatify-border">
        <li
          v-for="participant in conversation.relationships.participants"
          :key="participant.id"
          class="chatify:flex chatify:items-center chatify:gap-3 chatify:px-3 chatify:py-2"
        >
          <img
            :src="participant.attributes.avatar"
            :alt="participant.attributes.name"
            class="chatify:h-8 chatify:w-8 chatify:rounded-full"
          />
          <span class="chatify:flex-1 chatify:text-sm">{{ participant.attributes.name }}</span>
          <button
            v-if="conversation.attributes.is_owner && String(participant.id) !== String(configStore.user?.id)"
            type="button"
            class="chatify:text-xs chatify:text-chatify-danger"
            @click="removeParticipant(participant.id)"
          >
            Remove
          </button>
        </li>
      </ul>

      <div v-if="conversation.attributes.is_owner" class="chatify:space-y-2">
        <input
          v-model="search"
          type="search"
          placeholder="Search users to add"
          class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-2 chatify:text-sm chatify:focus:outline-none chatify:focus:ring-2 chatify:focus:ring-chatify-primary"
        />
        <ul v-if="contactsStore.searchResults.length" class="chatify:rounded-lg chatify:border chatify:border-chatify-border">
          <li
            v-for="user in contactsStore.searchResults"
            :key="user.id"
            class="chatify:flex chatify:items-center chatify:justify-between chatify:px-3 chatify:py-2"
          >
            <span class="chatify:text-sm">{{ user.attributes.name }}</span>
            <button
              type="button"
              class="chatify:text-xs chatify:text-chatify-primary"
              :disabled="adding"
              @click="addParticipant(user.id)"
            >
              Add
            </button>
          </li>
        </ul>
      </div>

      <button
        type="button"
        class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-chatify-danger chatify:py-2 chatify:text-sm chatify:text-chatify-danger"
        @click="leaveGroup"
      >
        Leave group
      </button>
    </div>
  </BaseModal>
</template>
