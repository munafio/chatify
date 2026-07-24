<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyUser } from '../../types'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { usePresenceStore } from '../../stores/presence'
import { useUiStore } from '../../stores/ui'
import { useConfirmStore } from '../../stores/confirm'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { displayUserAvatar, displayUserName, isIdentityHidden } from '../../utils/userDisplay'
import BaseModal from './BaseModal.vue'

const uiStore = useUiStore()
const contactsStore = useContactsStore()
const conversationsStore = useConversationsStore()
const presenceStore = usePresenceStore()
const confirmStore = useConfirmStore()
const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { activeModal, modalContext } = storeToRefs(uiStore)
const { defaultAvatarUrl } = storeToRefs(configStore)

const open = computed(() => activeModal.value === 'contactInfo')
const user = computed(() => modalContext.value.user as ChatifyUser | null)

const hiddenIdentity = computed(() => isIdentityHidden(user.value))

const isOnline = computed(() =>
  user.value
  && !hiddenIdentity.value
  && !contactsStore.isMessagingBlocked(user.value.id)
  && presenceStore.canShowPresence()
  && presenceStore.visibleOnline(user.value.id),
)

const onlineLabel = computed(() => {
  if (!user.value || hiddenIdentity.value || contactsStore.isMessagingBlocked(user.value.id) || !presenceStore.canShowPresence()) {
    return null
  }

  return isOnline.value ? t('ui.presence.online') : t('ui.presence.offline')
})

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

async function toggleBlock() {
  if (!user.value) {
    return
  }

  if (contactsStore.isBlocked(user.value.id)) {
    await contactsStore.unblockUser(user.value.id)
    return
  }

  const confirmed = await confirmStore.confirm({
    title: t('ui.confirm.block.title', { name: displayUserName(user.value) }),
    message: t('ui.confirm.block.message'),
    confirmLabel: t('ui.confirm.block.confirm'),
    variant: 'danger',
  })

  if (confirmed) {
    await contactsStore.blockUser(user.value.id)
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="t('ui.modals.contact_info.title')"
    @close="uiStore.closeModal()"
  >
    <div v-if="user" class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-4">
      <div class="chatify:relative">
        <img
          :src="displayUserAvatar(user, defaultAvatarUrl)"
          :alt="displayUserName(user)"
          class="chatify:h-24 chatify:w-24 chatify:rounded-full chatify:object-cover"
        />
        <span
          v-if="isOnline"
          class="chatify-presence-dot chatify-presence-dot-lg"
          :aria-label="$t('ui.presence.online')"
        />
      </div>
      <div class="chatify:text-center">
        <p class="chatify:text-lg chatify:font-semibold">{{ displayUserName(user) }}</p>
        <p v-if="onlineLabel" class="chatify:text-sm chatify:text-chatify-muted">
          {{ onlineLabel }}
        </p>
      </div>
      <div v-if="!hiddenIdentity" class="chatify:flex chatify:w-full chatify:flex-col chatify:gap-2">
        <button
          type="button"
          class="chatify:w-full chatify:rounded-lg chatify:bg-chatify-primary chatify:py-2 chatify:text-sm chatify:font-medium chatify:text-white"
          :disabled="contactsStore.isMessagingBlocked(user.id)"
          @click="startChat"
        >
          {{ $t('ui.modals.contact_info.message') }}
        </button>
        <div class="chatify:flex chatify:w-full chatify:gap-2">
          <button
            v-if="!contactsStore.isMessagingBlocked(user.id)"
            type="button"
            class="chatify:flex-1 chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:py-2 chatify:text-sm"
            @click="toggleFavorite"
          >
            {{ contactsStore.isFavorite(user.id) ? $t('ui.actions.conversation.unfavorite') : $t('ui.actions.conversation.favorite') }}
          </button>
          <button
            type="button"
            class="chatify:flex-1 chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:py-2 chatify:text-sm"
            :class="contactsStore.isBlocked(user.id) ? 'chatify:text-chatify-primary' : 'chatify:text-chatify-danger'"
            @click="toggleBlock"
          >
            {{ contactsStore.isBlocked(user.id) ? $t('ui.actions.conversation.unblock') : $t('ui.actions.conversation.block') }}
          </button>
        </div>
      </div>
    </div>
  </BaseModal>
</template>
