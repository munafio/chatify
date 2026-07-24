<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = withDefaults(
  defineProps<{
    size?: 'sm' | 'md' | 'lg'
    alt?: string
  }>(),
  {
    size: 'sm',
    alt: '',
  },
)

const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { user, avatarUploadProgress, avatarUploading, avatarRemoving } = storeToRefs(configStore)

const sizeClass = computed(() => {
  switch (props.size) {
    case 'lg':
      return 'chatify:h-24 chatify:w-24'
    case 'md':
      return 'chatify:h-16 chatify:w-16'
    default:
      return 'chatify:h-10 chatify:w-10'
  }
})

const iconClass = computed(() => {
  switch (props.size) {
    case 'lg':
      return 'chatify:h-10 chatify:w-10'
    case 'md':
      return 'chatify:h-8 chatify:w-8'
    default:
      return 'chatify:h-5 chatify:w-5'
  }
})

const progressLabelClass = computed(() => {
  switch (props.size) {
    case 'lg':
      return 'chatify:text-sm'
    case 'md':
      return 'chatify:text-xs'
    default:
      return 'chatify:text-[10px]'
  }
})

const displayName = computed(() => props.alt || user.value?.attributes.name || t('ui.user.default'))
const avatarSrc = computed(() => user.value?.attributes.avatar ?? '')
const hasAvatar = computed(() => Boolean(avatarSrc.value))
const showProgress = computed(() => avatarUploading.value && avatarUploadProgress.value !== null)
</script>

<template>
  <div
    class="chatify:relative chatify:shrink-0 chatify:rounded-full"
    :class="[
      sizeClass,
      avatarUploading ? 'chatify-avatar-uploading' : 'chatify-avatar-ring',
    ]"
  >
    <div class="chatify:relative chatify:h-full chatify:w-full chatify:overflow-hidden chatify:rounded-full chatify:bg-chatify-sidebar">
      <img
        v-if="hasAvatar"
        :src="avatarSrc"
        :alt="displayName"
        class="chatify:h-full chatify:w-full chatify:object-cover"
        :class="avatarUploading ? 'chatify-avatar-pulse' : ''"
      />
      <div
        v-else
        class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:text-chatify-muted"
        :class="avatarUploading ? 'chatify-avatar-pulse' : ''"
      >
        <svg :class="iconClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
      </div>

      <div
        v-if="avatarUploading"
        class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/45"
        aria-live="polite"
      >
        <span
          v-if="showProgress"
          class="chatify:font-semibold chatify:text-white"
          :class="progressLabelClass"
        >
          {{ avatarUploadProgress }}%
        </span>
        <span
          v-else-if="avatarRemoving"
          class="chatify:font-medium chatify:text-white/90"
          :class="progressLabelClass"
        >
          …
        </span>
      </div>
    </div>
  </div>
</template>
