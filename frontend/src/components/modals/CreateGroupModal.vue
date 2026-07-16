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

const name = ref('')
const search = ref('')
const selectedIds = ref<Array<number | string>>([])
const creating = ref(false)

const open = computed(() => activeModal.value === 'createGroup')

useDebouncedWatch(search, (query) => contactsStore.search(query))

watch(open, (isOpen) => {
  if (!isOpen) {
    name.value = ''
    search.value = ''
    selectedIds.value = []
    contactsStore.searchResults = []
  }
})

function toggleUser(id: number | string) {
  const key = String(id)
  if (selectedIds.value.some((item) => String(item) === key)) {
    selectedIds.value = selectedIds.value.filter((item) => String(item) !== key)
  } else {
    selectedIds.value.push(id)
  }
}

function isSelected(id: number | string) {
  return selectedIds.value.some((item) => String(item) === String(id))
}

async function create() {
  if (!name.value.trim() || selectedIds.value.length < 1) {
    return
  }

  creating.value = true
  try {
    await conversationsStore.createGroup(name.value.trim(), selectedIds.value)
    uiStore.closeModal()
    uiStore.showThread()
    name.value = ''
    search.value = ''
    selectedIds.value = []
    contactsStore.searchResults = []
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Create group"
    size="lg"
    @close="uiStore.closeModal()"
  >
    <div class="chatify:space-y-4">
      <input
        v-model="name"
        type="text"
        placeholder="Group name"
        class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-2 chatify:text-sm chatify:focus:outline-none chatify:focus:ring-2 chatify:focus:ring-chatify-primary"
      />

      <input
        v-model="search"
        type="search"
        placeholder="Search contacts by name or email"
        class="chatify:w-full chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-2 chatify:text-sm chatify:focus:outline-none chatify:focus:ring-2 chatify:focus:ring-chatify-primary"
      />

      <p v-if="contactsStore.searching" class="chatify:text-sm chatify:text-chatify-muted">Searching...</p>
      <p v-else-if="contactsStore.searchError" class="chatify:text-sm chatify:text-chatify-danger">
        {{ contactsStore.searchError }}
      </p>
      <p v-else-if="search.trim() && contactsStore.searchResults.length === 0" class="chatify:text-sm chatify:text-chatify-muted">
        No contacts found.
      </p>

      <ul
        v-if="contactsStore.searchResults.length"
        class="chatify:max-h-48 chatify:overflow-y-auto chatify:rounded-lg chatify:border chatify:border-chatify-border"
      >
        <li
          v-for="user in contactsStore.searchResults"
          :key="user.id"
          class="chatify:flex chatify:items-center chatify:gap-3 chatify:px-3 chatify:py-2 chatify:hover:bg-chatify-sidebar"
        >
          <input
            type="checkbox"
            :checked="isSelected(user.id)"
            @change="toggleUser(user.id)"
          />
          <img :src="user.attributes.avatar" :alt="user.attributes.name" class="chatify:h-8 chatify:w-8 chatify:rounded-full chatify:object-cover" />
          <div class="chatify:min-w-0 chatify:flex-1">
            <p class="chatify:truncate chatify:text-sm">{{ user.attributes.name }}</p>
            <p v-if="user.attributes.email" class="chatify:truncate chatify:text-xs chatify:text-chatify-muted">
              {{ user.attributes.email }}
            </p>
          </div>
        </li>
      </ul>

      <button
        type="button"
        class="chatify:w-full chatify:rounded-lg chatify:bg-chatify-primary chatify:py-2 chatify:text-sm chatify:font-medium chatify:text-white chatify:disabled:opacity-50"
        :disabled="creating || !name.trim() || selectedIds.length < 1"
        @click="create"
      >
        Create group
      </button>
    </div>
  </BaseModal>
</template>
