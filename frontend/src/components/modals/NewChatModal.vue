<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useDebouncedWatch } from '../../composables/useDebouncedFn'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import BaseModal from './BaseModal.vue'

const uiStore = useUiStore()
const contactsStore = useContactsStore()
const conversationsStore = useConversationsStore()
const { activeModal } = storeToRefs(uiStore)

const search = ref('')
const starting = ref(false)

const open = computed(() => activeModal.value === 'newChat')

useDebouncedWatch(search, (query) => contactsStore.search(query))

watch(open, (isOpen) => {
  if (!isOpen) {
    search.value = ''
    contactsStore.searchResults = []
  }
})

async function startChat(userId: number | string) {
  starting.value = true
  try {
    await conversationsStore.startDirect(userId)
    uiStore.closeModal()
    uiStore.showThread()
  } finally {
    starting.value = false
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    title="New chat"
    size="md"
    @close="uiStore.closeModal()"
  >
    <div class="chatify:space-y-4">
      <input
        v-model="search"
        type="search"
        placeholder="Search by name or email"
        class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-2 chatify:text-sm chatify:focus:outline-none chatify:focus:ring-2 chatify:focus:ring-chatify-primary"
      />

      <p v-if="contactsStore.searching" class="chatify:text-sm chatify:text-chatify-muted">
        Searching...
      </p>

      <p v-else-if="contactsStore.searchError" class="chatify:text-sm chatify:text-chatify-danger">
        {{ contactsStore.searchError }}
      </p>

      <p
        v-else-if="search.trim() && contactsStore.searchResults.length === 0"
        class="chatify:text-sm chatify:text-chatify-muted"
      >
        No contacts found.
      </p>

      <p v-else-if="!search.trim()" class="chatify:text-sm chatify:text-chatify-muted">
        Type to find people to message.
      </p>

      <ul
        v-else
        class="chatify:max-h-64 chatify:overflow-y-auto chatify:rounded-lg chatify:border chatify:border-chatify-border"
      >
        <li
          v-for="user in contactsStore.searchResults"
          :key="user.id"
          class="chatify:flex chatify:items-center chatify:gap-3 chatify:px-3 chatify:py-2 chatify:hover:bg-chatify-sidebar"
        >
          <img
            :src="user.attributes.avatar"
            :alt="user.attributes.name"
            class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover"
          />
          <div class="chatify:min-w-0 chatify:flex-1">
            <p class="chatify:truncate chatify:text-sm chatify:font-medium">{{ user.attributes.name }}</p>
            <p v-if="user.attributes.email" class="chatify:truncate chatify:text-xs chatify:text-chatify-muted">
              {{ user.attributes.email }}
            </p>
          </div>
          <button
            type="button"
            class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-white chatify:disabled:opacity-50"
            :disabled="starting"
            @click="startChat(user.id)"
          >
            Chat
          </button>
        </li>
      </ul>
    </div>
  </BaseModal>
</template>
