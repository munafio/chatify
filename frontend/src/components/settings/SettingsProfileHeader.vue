<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import UserAvatar from '../ui/UserAvatar.vue'
import { useConfigStore } from '../../stores/config'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'

const configStore = useConfigStore()
const { user, savedAvatarUrl, avatarUploading } = storeToRefs(configStore)

const fileInput = ref<HTMLInputElement | null>(null)
const menuOpen = ref(false)
const menuButton = ref<HTMLButtonElement | null>(null)
const menuPanel = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top: string; left: string }>({ top: '0px', left: '0px' })

const displayName = computed(() => user.value?.attributes.name ?? '')
const email = computed(() => user.value?.attributes.email ?? '')

const canRemove = computed(() => Boolean(savedAvatarUrl.value) && Boolean(user.value?.attributes.avatar))

function updateMenuPosition() {
  if (!menuButton.value) {
    return
  }

  const rect = menuButton.value.getBoundingClientRect()
  const menuWidth = menuPanel.value?.offsetWidth ?? 152

  menuStyle.value = {
    top: `${rect.bottom + 4}px`,
    left: `${Math.max(8, rect.right - menuWidth)}px`,
  }
}

function openPicker() {
  if (avatarUploading.value) {
    return
  }

  menuOpen.value = false
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

  menuOpen.value = false
  await configStore.removeAvatar()
}

async function toggleMenu() {
  if (avatarUploading.value) {
    return
  }

  menuOpen.value = !menuOpen.value

  if (menuOpen.value) {
    await nextTick()
    updateMenuPosition()
  }
}

function onDocumentClick(event: MouseEvent) {
  if (!menuOpen.value) {
    return
  }

  const target = event.target as Node
  if (menuButton.value?.contains(target) || menuPanel.value?.contains(target)) {
    return
  }

  menuOpen.value = false
}

watch(menuOpen, (isOpen) => {
  if (!isOpen) {
    return
  }

  void nextTick(() => {
    updateMenuPosition()
  })
})

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  window.addEventListener('resize', updateMenuPosition)
  window.addEventListener('scroll', updateMenuPosition, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
  window.removeEventListener('resize', updateMenuPosition)
  window.removeEventListener('scroll', updateMenuPosition, true)
})
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

    <div class="chatify:shrink-0">
      <button
        ref="menuButton"
        type="button"
        class="chatify:flex chatify:h-9 chatify:w-9 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-chatify-muted chatify:transition chatify:hover:bg-chatify-sidebar chatify:hover:text-chatify-text disabled:chatify:opacity-50"
        aria-label="Avatar options"
        aria-haspopup="menu"
        :aria-expanded="menuOpen"
        :disabled="avatarUploading"
        @click.stop="toggleMenu"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="5" r="1.75" />
          <circle cx="12" cy="12" r="1.75" />
          <circle cx="12" cy="19" r="1.75" />
        </svg>
      </button>
    </div>

    <Teleport :to="CHATIFY_TELEPORT_TARGET">
      <div
        v-if="menuOpen"
        ref="menuPanel"
        class="chatify-profile-menu chatify-profile-menu-floating"
        role="menu"
        :style="menuStyle"
      >
        <button type="button" class="chatify-profile-menu-item" role="menuitem" @click="openPicker">
          Change photo
        </button>
        <button
          v-if="canRemove"
          type="button"
          class="chatify-profile-menu-item chatify-profile-menu-item-danger"
          role="menuitem"
          @click="removeAvatar"
        >
          Remove photo
        </button>
      </div>
    </Teleport>

    <input
      ref="fileInput"
      type="file"
      accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
      class="chatify:hidden"
      @change="onFileSelected"
    />
  </div>
</template>
