<script setup lang="ts">
import { computed, ref } from 'vue'
import { useChatifyDirection } from '../../composables/useChatifyDirection'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { useConfigStore } from '../../stores/config'
import { storeToRefs } from 'pinia'
import UserAvatar from '../ui/UserAvatar.vue'
import DropdownMenu, { type DropdownMenuItem } from '../ui/DropdownMenu.vue'

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { isRtl } = useChatifyDirection()
const { user, savedAvatarUrl, avatarUploading } = storeToRefs(configStore)

const fileInput = ref<HTMLInputElement | null>(null)

const displayName = computed(() => user.value?.attributes.name ?? '')
const email = computed(() => user.value?.attributes.email ?? '')

const canRemove = computed(() => Boolean(savedAvatarUrl.value) && Boolean(user.value?.attributes.avatar))

const menuItems = computed<DropdownMenuItem[]>(() => {
  const items: DropdownMenuItem[] = [
    { id: 'change', label: t('ui.settings.change_photo') },
  ]

  if (canRemove.value) {
    items.push({ id: 'remove', label: t('ui.settings.remove_photo'), danger: true })
  }

  return items
})

function openPicker() {
  if (avatarUploading.value) {
    return
  }

  fileInput.value?.click()
}

async function onFileSelected(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }

  await configStore.uploadAvatar(file)
  ;(event.target as HTMLInputElement).value = ''
}

async function removeAvatar() {
  if (avatarUploading.value) {
    return
  }

  await configStore.removeAvatar()
}

function onMenuSelect(id: string) {
  if (id === 'change') {
    openPicker()
    return
  }

  if (id === 'remove') {
    void removeAvatar()
  }
}
</script>

<template>
  <div v-if="user" class="chatify:mb-3 chatify:flex chatify:items-center chatify:gap-3">
    <UserAvatar size="md" :alt="displayName" />

    <div class="chatify:min-w-0 chatify:flex-1">
      <p class="chatify:truncate chatify:text-base chatify:font-semibold chatify:text-chatify-text">
        {{ displayName }}
      </p>
      <p v-if="email" class="chatify:truncate chatify:text-sm chatify:text-chatify-muted">
        {{ email }}
      </p>
    </div>

    <DropdownMenu
      :items="menuItems"
      :align="isRtl ? 'start' : 'end'"
      :disabled="avatarUploading"
      @select="onMenuSelect"
    >
      <template #trigger>
        <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="5" r="1.75" />
          <circle cx="12" cy="12" r="1.75" />
          <circle cx="12" cy="19" r="1.75" />
        </svg>
      </template>
    </DropdownMenu>

    <input
      ref="fileInput"
      type="file"
      accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
      class="chatify:hidden"
      @change="onFileSelected"
    />
  </div>
</template>
