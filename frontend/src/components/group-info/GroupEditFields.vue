<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  name: string
  description: string | null | undefined
  editable: boolean
}>()

const emit = defineEmits<{
  saveName: [value: string]
  saveDescription: [value: string | null]
}>()

const editingName = ref(false)
const editingDescription = ref(false)
const nameDraft = ref('')
const descriptionDraft = ref('')
const nameInput = ref<HTMLInputElement | null>(null)
const descriptionInput = ref<HTMLTextAreaElement | null>(null)
const nameEditTrigger = ref<HTMLButtonElement | null>(null)
const descriptionEditTrigger = ref<HTMLButtonElement | null>(null)
const { t } = useChatifyI18n()

watch(
  () => props.name,
  (value) => {
    if (!editingName.value) {
      nameDraft.value = value ?? ''
    }
  },
  { immediate: true },
)

watch(
  () => props.description,
  (value) => {
    if (!editingDescription.value) {
      descriptionDraft.value = value ?? ''
    }
  },
  { immediate: true },
)

watch(editingName, async (isEditing) => {
  if (isEditing) {
    nameDraft.value = props.name ?? ''
    await nextTick()
    nameInput.value?.focus()
    nameInput.value?.select()
    document.addEventListener('mousedown', onNameOutsideClick)
    document.addEventListener('keydown', onNameKeydown)
    return
  }

  document.removeEventListener('mousedown', onNameOutsideClick)
  document.removeEventListener('keydown', onNameKeydown)
})

watch(editingDescription, async (isEditing) => {
  if (isEditing) {
    descriptionDraft.value = props.description ?? ''
    await nextTick()
    descriptionInput.value?.focus()
    document.addEventListener('mousedown', onDescriptionOutsideClick)
    document.addEventListener('keydown', onDescriptionKeydown)
    return
  }

  document.removeEventListener('mousedown', onDescriptionOutsideClick)
  document.removeEventListener('keydown', onDescriptionKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onNameOutsideClick)
  document.removeEventListener('keydown', onNameKeydown)
  document.removeEventListener('mousedown', onDescriptionOutsideClick)
  document.removeEventListener('keydown', onDescriptionKeydown)
})

function cancelName() {
  nameDraft.value = props.name ?? ''
  editingName.value = false
}

function saveName() {
  editingName.value = false
  emit('saveName', nameDraft.value.trim())
}

function cancelDescription() {
  descriptionDraft.value = props.description ?? ''
  editingDescription.value = false
}

function saveDescription() {
  editingDescription.value = false
  emit('saveDescription', descriptionDraft.value.trim() || null)
}

function onNameOutsideClick(event: MouseEvent) {
  const target = event.target as Node
  if (nameInput.value?.contains(target) || nameEditTrigger.value?.contains(target)) {
    return
  }
  cancelName()
}

function onDescriptionOutsideClick(event: MouseEvent) {
  const target = event.target as Node
  if (descriptionInput.value?.contains(target) || descriptionEditTrigger.value?.contains(target)) {
    return
  }
  cancelDescription()
}

function onNameKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    event.preventDefault()
    cancelName()
    return
  }

  if (event.key === 'Enter') {
    event.preventDefault()
    saveName()
  }
}

function onDescriptionKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    event.preventDefault()
    cancelDescription()
    return
  }

  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    saveDescription()
  }
}
</script>

<template>
  <div class="chatify:w-full chatify:space-y-2 chatify:text-center">
    <div class="chatify:flex chatify:items-center chatify:justify-center chatify:gap-2">
      <input
        v-if="editingName"
        ref="nameInput"
        v-model="nameDraft"
        type="text"
        class="chatify-field-underline chatify:max-w-xs chatify:text-center chatify:text-base chatify:font-semibold"
        @keydown="onNameKeydown"
      />
      <template v-else>
        <h3 class="chatify:text-base chatify:font-semibold">{{ name || t('ui.format.group_label') }}</h3>
        <button
          v-if="editable"
          ref="nameEditTrigger"
          type="button"
          class="chatify:rounded-full chatify:p-1 chatify:text-chatify-muted chatify:transition chatify:hover:bg-chatify-sidebar"
          :aria-label="t('ui.group.info.edit_name')"
          @click="editingName = true"
        >
          <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-.878.515l-3.2.8.8-3.2a2 2 0 01.515-.878z" />
          </svg>
        </button>
      </template>
    </div>

    <div class="chatify:flex chatify:items-start chatify:justify-center">
      <textarea
        v-if="editingDescription"
        ref="descriptionInput"
        v-model="descriptionDraft"
        rows="2"
        class="chatify-field-underline chatify:max-w-sm chatify:resize-none chatify:text-center chatify:text-sm"
        :placeholder="t('ui.group.info.description_placeholder')"
        @keydown="onDescriptionKeydown"
      />
      <template v-else>
        <button
          v-if="editable"
          ref="descriptionEditTrigger"
          type="button"
          class="chatify:text-sm chatify:transition chatify:hover:opacity-80"
          :class="description ? 'chatify:text-chatify-text' : 'chatify:text-chatify-primary'"
          @click="editingDescription = true"
        >
          {{ description || t('ui.group.info.description_placeholder') }}
        </button>
        <p v-else-if="description" class="chatify:text-sm chatify:text-chatify-muted">{{ description }}</p>
      </template>
    </div>
  </div>
</template>
