<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useTyping } from '../../composables/useTyping'
import { useConfigStore } from '../../stores/config'
import { useMessagesStore } from '../../stores/messages'

const props = defineProps<{
  conversationId: string
}>()

const messagesStore = useMessagesStore()
const configStore = useConfigStore()
const { replyToMessage, editingMessage } = storeToRefs(messagesStore)

const body = ref('')
const attachments = ref<File[]>([])
const previewUrls = ref<string[]>([])
const fileInput = ref<HTMLInputElement | null>(null)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const TEXTAREA_MAX_HEIGHT = 128

const { notifyTyping, stopTyping } = useTyping(() => props.conversationId)

const accept = [
  ...(configStore.attachments?.allowedImages.map((ext) => `.${ext}`) ?? []),
  ...(configStore.attachments?.allowedFiles.map((ext) => `.${ext}`) ?? []),
].join(',')

const imageOnlyAccept = (configStore.attachments?.allowedImages.map((ext) => `.${ext}`) ?? []).join(',')

const modeLabel = computed(() => {
  if (editingMessage.value) {
    return 'Editing message'
  }
  if (replyToMessage.value) {
    return `Replying to ${replyToMessage.value.attributes.body?.slice(0, 80) ?? 'attachment'}`
  }
  return ''
})

watch(editingMessage, async (message) => {
  if (message) {
    body.value = message.attributes.body ?? ''
    clearAttachments()
    await nextTick()
    resizeTextarea()
  }
})

function revokePreviews() {
  previewUrls.value.forEach((url) => URL.revokeObjectURL(url))
  previewUrls.value = []
}

function clearAttachments() {
  revokePreviews()
  attachments.value = []
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function resetTextareaHeight() {
  const el = textareaRef.value
  if (!el) {
    return
  }

  el.style.height = '2.25rem'
  el.style.overflowY = 'hidden'
}

function resizeTextarea() {
  const el = textareaRef.value
  if (!el) {
    return
  }

  el.style.height = 'auto'
  const nextHeight = Math.min(el.scrollHeight, TEXTAREA_MAX_HEIGHT)
  el.style.height = `${Math.max(nextHeight, 36)}px`
  el.style.overflowY = el.scrollHeight > TEXTAREA_MAX_HEIGHT ? 'auto' : 'hidden'
}

function cancelMode() {
  messagesStore.setReplyTo(null)
  messagesStore.setEditingMessage(null)
  body.value = ''
  clearAttachments()
  resetTextareaHeight()
}

function buildReplyPreview() {
  const reply = replyToMessage.value
  if (!reply) {
    return null
  }

  const isOwnReplyTarget =
    configStore.user &&
    String(reply.relationships.sender.data.id) === String(configStore.user.id)

  return {
    id: reply.id,
    body: reply.attributes.body,
    sender_name: isOwnReplyTarget ? 'You' : 'Reply',
  }
}

async function send() {
  const text = body.value.trim()
  if (!text && attachments.value.length === 0) {
    return
  }

  const editing = editingMessage.value
  const replyId = replyToMessage.value?.id
  const replyPreview = buildReplyPreview()
  const imageFiles = attachments.value.filter((file) => file.type.startsWith('image/'))
  const otherFiles = attachments.value.filter((file) => !file.type.startsWith('image/'))

  body.value = ''
  clearAttachments()
  resetTextareaHeight()
  stopTyping()

  if (editing) {
    messagesStore.setEditingMessage(null)
    try {
      await messagesStore.updateMessage(editing, text)
    } catch {
      messagesStore.setEditingMessage(editing)
      body.value = text
      await nextTick()
      resizeTextarea()
    }
    return
  }

  messagesStore.setReplyTo(null)

  messagesStore.queueOutboundMessage({
    conversationId: props.conversationId,
    body: text,
    attachment: otherFiles[0],
    attachments: imageFiles.length > 0 ? imageFiles : undefined,
    reply_to_message_id: replyId,
    reply_to: replyPreview,
  })
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    void send()
    return
  }

  if (event.key === 'Enter' && event.shiftKey) {
    requestAnimationFrame(resizeTextarea)
  }
}

function onInput() {
  notifyTyping()
  resizeTextarea()
}

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files ?? [])
  if (files.length === 0) {
    return
  }

  revokePreviews()
  attachments.value = files
  previewUrls.value = files
    .filter((file) => file.type.startsWith('image/'))
    .map((file) => URL.createObjectURL(file))
}

function removeAttachment(index: number) {
  attachments.value = attachments.value.filter((_, i) => i !== index)
  if (previewUrls.value[index]) {
    URL.revokeObjectURL(previewUrls.value[index])
    previewUrls.value = previewUrls.value.filter((_, i) => i !== index)
  }
}

function openPicker(multiple = true) {
  if (fileInput.value) {
    fileInput.value.multiple = multiple
    fileInput.value.accept = multiple ? imageOnlyAccept || accept : accept
    fileInput.value.click()
  }
}

onBeforeUnmount(revokePreviews)

const hasComposerExtras = computed(
  () => Boolean(modeLabel.value) || previewUrls.value.length > 0,
)
</script>

<template>
  <footer class="chatify-composer-footer chatify-sidebar-divide chatify:shrink-0 chatify:border-t chatify:bg-chatify-sidebar">
    <div v-if="hasComposerExtras" class="chatify-composer-extras chatify:px-4 chatify:pt-2 chatify:pb-1">
      <div
        v-if="modeLabel"
        class="chatify:mb-2 chatify:flex chatify:items-center chatify:justify-between chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:bg-chatify-bubble-in chatify:px-3 chatify:py-2 chatify:text-xs"
      >
        <span class="chatify:truncate chatify:text-chatify-muted">{{ modeLabel }}</span>
        <button type="button" class="chatify:ml-2 chatify:text-chatify-muted chatify:hover:text-chatify-text" @click="cancelMode">
          ✕
        </button>
      </div>

      <div v-if="previewUrls.length > 0" class="chatify:mb-3 chatify:flex chatify:flex-wrap chatify:gap-2">
        <div
          v-for="(url, index) in previewUrls"
          :key="url"
          class="chatify:relative chatify:h-16 chatify:w-16 chatify:overflow-hidden chatify:rounded-md"
        >
          <img :src="url" alt="" class="chatify:h-full chatify:w-full chatify:object-cover" />
          <button
            type="button"
            class="chatify:absolute chatify:right-0.5 chatify:top-0.5 chatify:rounded-full chatify:bg-black/60 chatify:px-1 chatify:text-[10px] chatify:text-white"
            @click="removeAttachment(index)"
          >
            ✕
          </button>
        </div>
      </div>
    </div>

    <div class="chatify-composer-bar chatify:flex chatify:items-end chatify:gap-2 chatify:px-4">
      <input
        ref="fileInput"
        type="file"
        class="chatify:hidden"
        multiple
        :accept="accept"
        @change="onFileChange"
      />

      <button
        type="button"
        class="chatify:mb-0.5 chatify:shrink-0 chatify:rounded-full chatify:p-2 chatify:text-chatify-muted chatify:hover:bg-chatify-border"
        aria-label="Attach images"
        @click="openPicker(true)"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </button>

      <button
        type="button"
        class="chatify:mb-0.5 chatify:shrink-0 chatify:rounded-full chatify:p-2 chatify:text-chatify-muted chatify:hover:bg-chatify-border"
        aria-label="Attach file"
        @click="openPicker(false)"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
      </button>

      <textarea
        ref="textareaRef"
        v-model="body"
        rows="1"
        placeholder="Type a message"
        class="chatify-composer-input chatify:flex-1 chatify:resize-none chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:bg-chatify-bubble-in chatify:px-3 chatify:py-2 chatify:text-sm chatify:text-chatify-text chatify:focus:outline-none chatify:focus:ring-2 chatify:focus:ring-chatify-primary"
        @keydown="onKeydown"
        @input="onInput"
      />

      <button
        type="button"
        class="chatify:mb-0.5 chatify:shrink-0 chatify:rounded-full chatify:bg-chatify-primary chatify-accent-gradient chatify:p-2 chatify:text-white chatify:disabled:opacity-50"
        :disabled="!body.trim() && attachments.length === 0"
        aria-label="Send message"
        @click="send"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
        </svg>
      </button>
    </div>
  </footer>
</template>
