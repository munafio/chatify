<script setup lang="ts">
import { computed, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    src?: string | null
    name?: string
    editable?: boolean
    uploading?: boolean
    uploadProgress?: number | null
  }>(),
  {
    src: null,
    name: 'Group',
    editable: false,
    uploading: false,
    uploadProgress: null,
  },
)

const emit = defineEmits<{
  upload: [file: File]
}>()

const fileInput = ref<HTMLInputElement | null>(null)

const showProgress = computed(() => props.uploading && props.uploadProgress !== null)

function openPicker() {
  if (!props.editable || props.uploading) {
    return
  }

  fileInput.value?.click()
}

function onFileChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) {
    emit('upload', file)
  }
  ;(event.target as HTMLInputElement).value = ''
}
</script>

<template>
  <div class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-2">
    <div
      class="chatify:relative chatify:h-28 chatify:w-28 chatify:shrink-0 chatify:rounded-full"
      :class="uploading ? 'chatify-avatar-uploading' : 'chatify-avatar-ring'"
    >
      <div class="chatify:relative chatify:h-full chatify:w-full chatify:overflow-hidden chatify:rounded-full chatify:bg-chatify-sidebar">
        <img
          v-if="src"
          :src="src"
          :alt="name"
          class="chatify:h-full chatify:w-full chatify:object-cover"
          :class="uploading ? 'chatify-avatar-pulse' : ''"
        />
        <div
          v-else
          class="chatify:flex chatify:h-full chatify:w-full chatify:items-center chatify:justify-center chatify:bg-chatify-primary-dark chatify:text-white"
          :class="uploading ? 'chatify-avatar-pulse' : ''"
        >
          <svg class="chatify:h-10 chatify:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>

        <div
          v-if="uploading"
          class="chatify:absolute chatify:inset-0 chatify:flex chatify:items-center chatify:justify-center chatify:bg-black/45"
          aria-live="polite"
        >
          <span v-if="showProgress" class="chatify:text-sm chatify:font-semibold chatify:text-white">
            {{ uploadProgress }}%
          </span>
        </div>
      </div>
    </div>

    <button
      v-if="editable"
      type="button"
      class="chatify:text-xs chatify:text-chatify-primary chatify:transition chatify:hover:opacity-80 disabled:chatify:opacity-50"
      :disabled="uploading"
      @click="openPicker"
    >
      Change image
    </button>

    <input
      ref="fileInput"
      type="file"
      accept="image/png,image/jpeg,image/jpg,image/gif,image/webp"
      class="chatify:hidden"
      @change="onFileChange"
    />
  </div>
</template>
