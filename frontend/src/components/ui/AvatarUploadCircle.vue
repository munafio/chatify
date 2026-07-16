<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    src: string
    alt: string
    saving?: boolean
    accept?: string
  }>(),
  {
    saving: false,
    accept: 'image/png,image/jpeg,image/jpg,image/gif,image/webp',
  },
)

const emit = defineEmits<{
  upload: [file: File]
}>()

const inputRef = ref<HTMLInputElement | null>(null)
const previewUrl = ref<string | null>(null)
const pendingFile = ref<File | null>(null)

const displaySrc = computed(() => previewUrl.value ?? props.src)

function revokePreview() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
}

watch(
  () => props.src,
  () => {
    if (!pendingFile.value) {
      revokePreview()
    }
  },
)

onUnmounted(revokePreview)

function openPicker() {
  if (!props.saving) {
    inputRef.value?.click()
  }
}

function onFileChange(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) {
    return
  }

  pendingFile.value = file
  revokePreview()
  previewUrl.value = URL.createObjectURL(file)
  emit('upload', file)

  if (inputRef.value) {
    inputRef.value.value = ''
  }
}

function cancelPreview() {
  pendingFile.value = null
  revokePreview()
}
</script>

<template>
  <div class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-3">
    <button
      type="button"
      class="chatify:group chatify:relative chatify:h-24 chatify:w-24 chatify:overflow-hidden chatify:rounded-full chatify:border-2 chatify:border-chatify-border chatify:bg-chatify-sidebar chatify:shadow-sm chatify:disabled:opacity-60"
      :disabled="saving"
      @click="openPicker"
    >
      <img
        :src="displaySrc"
        :alt="alt"
        class="chatify:h-full chatify:w-full chatify:object-cover"
      />

      <span
        class="chatify:absolute chatify:inset-0 chatify:flex chatify:flex-col chatify:items-center chatify:justify-center chatify:bg-black/45 chatify:text-white chatify:opacity-0 chatify:transition-opacity group-hover:chatify:opacity-100 group-focus-visible:chatify:opacity-100"
      >
        <svg class="chatify:h-6 chatify:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="chatify:mt-1 chatify:text-[11px] chatify:font-medium">
          {{ saving ? 'Uploading...' : 'Update' }}
        </span>
      </span>
    </button>

    <input
      ref="inputRef"
      type="file"
      class="chatify:hidden"
      :accept="accept"
      @change="onFileChange"
    />

    <div class="chatify:text-center">
      <p class="chatify:text-sm chatify:font-medium">{{ alt }}</p>
      <p class="chatify:text-xs chatify:text-chatify-muted">Click photo to upload a new avatar</p>
    </div>

    <button
      v-if="pendingFile && !saving"
      type="button"
      class="chatify:text-xs chatify:font-medium chatify:text-chatify-danger"
      @click="cancelPreview"
    >
      Cancel preview
    </button>
  </div>
</template>
