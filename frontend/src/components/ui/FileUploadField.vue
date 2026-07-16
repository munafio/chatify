<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: File | null
    accept?: string
    label?: string
    hint?: string
    variant?: 'avatar' | 'attachment'
  }>(),
  {
    accept: 'image/*',
    label: 'Choose file',
    hint: 'Click to browse or drag a file here',
    variant: 'attachment',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: File | null]
}>()

const inputRef = ref<HTMLInputElement | null>(null)
const previewUrl = ref<string | null>(null)
const isDragging = ref(false)

const isImage = computed(() => {
  if (!props.modelValue) {
    return false
  }

  return props.modelValue.type.startsWith('image/')
})

function revokePreview() {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = null
  }
}

watch(
  () => props.modelValue,
  (file) => {
    revokePreview()
    if (file && file.type.startsWith('image/')) {
      previewUrl.value = URL.createObjectURL(file)
    }
  },
  { immediate: true },
)

onUnmounted(revokePreview)

function setFile(file: File | null) {
  emit('update:modelValue', file)
}

function onInputChange(event: Event) {
  const target = event.target as HTMLInputElement
  setFile(target.files?.[0] ?? null)
}

function clear() {
  setFile(null)
  if (inputRef.value) {
    inputRef.value.value = ''
  }
}

function openPicker() {
  inputRef.value?.click()
}

function onDrop(event: DragEvent) {
  event.preventDefault()
  isDragging.value = false
  const file = event.dataTransfer?.files?.[0] ?? null
  if (file) {
    setFile(file)
  }
}
</script>

<template>
  <div class="chatify:space-y-2">
    <input
      ref="inputRef"
      type="file"
      class="chatify:hidden"
      :accept="accept"
      @change="onInputChange"
    />

    <div
      v-if="modelValue"
      class="chatify:flex chatify:items-center chatify:gap-3 chatify:rounded-xl chatify:border chatify:border-chatify-border chatify:bg-chatify-bubble-in chatify:p-3"
    >
      <img
        v-if="isImage && previewUrl"
        :src="previewUrl"
        :alt="modelValue.name"
        class="chatify:object-cover"
        :class="variant === 'avatar' ? 'chatify:h-16 chatify:w-16 chatify:rounded-full' : 'chatify:h-14 chatify:w-14 chatify:rounded-lg'"
      />
      <div
        v-else
        class="chatify:flex chatify:h-14 chatify:w-14 chatify:items-center chatify:justify-center chatify:rounded-lg chatify:bg-chatify-sidebar chatify:text-chatify-muted"
      >
        <svg class="chatify:h-6 chatify:w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>

      <div class="chatify:min-w-0 chatify:flex-1">
        <p class="chatify:truncate chatify:text-sm chatify:font-medium">{{ modelValue.name }}</p>
        <p class="chatify:text-xs chatify:text-chatify-muted">
          {{ (modelValue.size / 1024).toFixed(1) }} KB
        </p>
      </div>

      <div class="chatify:flex chatify:gap-2">
        <button
          type="button"
          class="chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:hover:bg-chatify-sidebar"
          @click="openPicker"
        >
          Replace
        </button>
        <button
          type="button"
          class="chatify:rounded-lg chatify:border chatify:border-chatify-danger chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-chatify-danger chatify:hover:bg-red-50"
          @click="clear"
        >
          Cancel
        </button>
      </div>
    </div>

    <button
      v-else
      type="button"
      class="chatify:flex chatify:w-full chatify:flex-col chatify:items-center chatify:justify-center chatify:gap-2 chatify:rounded-xl chatify:border-2 chatify:border-dashed chatify:px-4 chatify:py-6 chatify:text-center chatify:transition-colors"
      :class="isDragging ? 'chatify:border-chatify-primary chatify:bg-green-50' : 'chatify:border-chatify-border chatify:bg-chatify-sidebar chatify:hover:border-chatify-primary'"
      @click="openPicker"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop="onDrop"
    >
      <svg class="chatify:h-8 chatify:w-8 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
      </svg>
      <span class="chatify:text-sm chatify:font-medium">{{ label }}</span>
      <span class="chatify:text-xs chatify:text-chatify-muted">{{ hint }}</span>
    </button>
  </div>
</template>
