<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyUser } from '../../types'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import BaseModal from './BaseModal.vue'

const uiStore = useUiStore()
const contactsStore = useContactsStore()
const conversationsStore = useConversationsStore()
const { activeModal, modalContext } = storeToRefs(uiStore)

const open = computed(() => activeModal.value === 'contactInfo')
const user = computed(() => modalContext.value.user as ChatifyUser | null)

async function startChat() {
  if (!user.value) {
    return
  }
  await conversationsStore.startDirect(user.value.id)
  uiStore.closeModal()
  uiStore.showThread()
}

async function toggleFavorite() {
  if (!user.value) {
    return
  }
  await contactsStore.toggleFavorite(user.value.id)
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Contact info"
    @close="uiStore.closeModal()"
  >
    <div v-if="user" class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-4">
      <img
        :src="user.attributes.avatar"
        :alt="user.attributes.name"
        class="chatify:h-24 chatify:w-24 chatify:rounded-full chatify:object-cover"
      />
      <div class="chatify:text-center">
        <p class="chatify:text-lg chatify:font-semibold">{{ user.attributes.name }}</p>
      </div>
      <div class="chatify:flex chatify:w-full chatify:gap-2">
        <button
          type="button"
          class="chatify:flex-1 chatify:rounded-lg chatify:bg-chatify-primary chatify:py-2 chatify:text-sm chatify:font-medium chatify:text-white"
          @click="startChat"
        >
          Message
        </button>
        <button
          type="button"
          class="chatify:flex-1 chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:py-2 chatify:text-sm"
          @click="toggleFavorite"
        >
          {{ contactsStore.isFavorite(user.id) ? 'Unfavorite' : 'Favorite' }}
        </button>
      </div>
    </div>
  </BaseModal>
</template>
