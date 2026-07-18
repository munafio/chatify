<script setup lang="ts">
import { computed, ref } from 'vue'
import type { ChatifyMessage } from '../../types'
import { formatMessageTime } from '../../utils/format'
import { isPendingMessageId } from '../../utils/outboundMessage'
import { bubbleTextClass } from '../../themes/utils'
import { useImageLightbox } from '../../composables/useImageLightbox'
import MessageAlbumBubble from './MessageAlbumBubble.vue'
import MessageActionsMenu from './MessageActionsMenu.vue'
import MessageBody from './MessageBody.vue'
import MessageDeliveryStatus from './MessageDeliveryStatus.vue'
import MessageDocCard from './MessageDocCard.vue'
import VoiceMessageBubble from './VoiceMessageBubble.vue'

const props = defineProps<{
  message: ChatifyMessage
  isOwn: boolean
  isGroup?: boolean
  senderName?: string
  showSenderName?: boolean
  clusterSpacing?: 'tight' | 'normal'
}>()

const emit = defineEmits<{
  edit: []
  removeForMe: []
  removeForAll: []
  reply: []
  forward: []
  resend: []
  cancel: []
  jumpTo: [messageId: string]
}>()

const { show } = useImageLightbox()

const localStatus = computed(() => props.message.attributes.local_status ?? null)
const isPending = computed(() => isPendingMessageId(props.message.id))
const showActions = computed(() => !isPending.value && localStatus.value !== 'sending')
const spacingClass = computed(() =>
  props.clusterSpacing === 'tight' ? 'chatify:mt-0.5' : 'chatify:mt-2',
)

const imageLoaded = ref(false)

const uploadProgress = computed(() => {
  const value = props.message.attributes.upload_progress
  return typeof value === 'number' ? value : null
})

const isUploading = computed(
  () =>
    isPending.value &&
    uploadProgress.value !== null &&
    uploadProgress.value < 100,
)

function attachments() {
  if (props.message.attributes.attachments?.length) {
    return props.message.attributes.attachments
  }
  return props.message.attributes.attachment ? [props.message.attributes.attachment] : []
}

function isImageAlbum() {
  const items = attachments()
  return items.length > 1 && items.every((item) => item.type === 'image')
}

function isAudioAttachment() {
  return props.message.attributes.attachment?.type === 'audio'
}

function isVideoAttachment() {
  return props.message.attributes.attachment?.type === 'video'
}

function openSingleImage() {
  const attachment = props.message.attributes.attachment
  if (attachment?.type === 'image') {
    show([attachment], 0)
  }
}
</script>

<template>
  <div
    class="chatify:flex chatify:w-full chatify:flex-col chatify:gap-1"
    :class="spacingClass"
  >
    <div
      class="chatify:group chatify:flex chatify:w-full chatify:items-end chatify:gap-1"
      :class="isOwn ? 'chatify:justify-end' : 'chatify:justify-start'"
    >
      <MessageActionsMenu
        v-if="isOwn && showActions"
        :is-own="isOwn"
        @edit="emit('edit')"
        @remove-for-me="emit('removeForMe')"
        @remove-for-all="emit('removeForAll')"
        @reply="emit('reply')"
        @forward="emit('forward')"
      />

      <div
        class="chatify-message-bubble chatify:max-w-[75%] chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:shadow-sm"
        :class="[
          isOwn ? `chatify-message-bubble-out chatify:bg-chatify-bubble-out ${bubbleTextClass(true)}` : `chatify-message-bubble-in chatify:bg-chatify-bubble-in ${bubbleTextClass(false)}`,
          localStatus === 'sending' ? 'chatify-message-pending' : '',
          localStatus === 'failed' ? 'chatify-message-failed' : '',
        ]"
      >
        <p
          v-if="showSenderName && senderName"
          class="chatify:mb-1 chatify:text-xs chatify:font-semibold chatify:text-chatify-primary-dark"
        >
          {{ senderName }}
        </p>

        <div
          v-if="message.attributes.forwarded_from"
          class="chatify:mb-2 chatify:border-l-2 chatify:border-chatify-primary chatify:pl-2 chatify:text-xs chatify:italic chatify:opacity-80"
        >
          <p>Forwarded</p>
        </div>

        <button
          v-if="message.attributes.reply_to"
          type="button"
          class="chatify:mb-2 chatify:block chatify:w-full chatify:cursor-pointer chatify:rounded chatify:border-l-2 chatify:border-chatify-primary chatify:bg-black/5 chatify:px-2 chatify:py-1 chatify:text-left chatify:text-xs chatify:opacity-80 chatify:transition chatify:hover:opacity-100"
          @click="emit('jumpTo', message.attributes.reply_to.id)"
        >
          <span class="chatify:block chatify:font-semibold">{{ message.attributes.reply_to.sender_name }}</span>
          <span class="chatify:block chatify:truncate">{{ message.attributes.reply_to.body || 'Attachment' }}</span>
        </button>

        <div v-if="isImageAlbum()" class="chatify:relative">
          <MessageAlbumBubble :attachments="attachments()" />
          <div
            v-if="isUploading"
            class="chatify-attachment-progress-overlay chatify:rounded-lg"
          >
            <div class="chatify-attachment-progress-ring">{{ uploadProgress }}%</div>
            <button
              type="button"
              class="chatify-attachment-progress-cancel"
              aria-label="Cancel upload"
              @click="emit('cancel')"
            >
              ✕
            </button>
          </div>
        </div>

        <template v-else>
          <VoiceMessageBubble
            v-if="isAudioAttachment() && message.attributes.attachment"
            :attachment="message.attributes.attachment"
            :uploading="isUploading"
            :progress="uploadProgress"
            @cancel="emit('cancel')"
          />

          <div
            v-else-if="isVideoAttachment() && message.attributes.attachment"
            class="chatify:relative chatify:mb-1 chatify:inline-block chatify:overflow-hidden chatify:rounded-md"
          >
            <video
              :src="message.attributes.attachment.url"
              controls
              preload="metadata"
              class="chatify:max-h-52 chatify:max-w-[15rem] chatify:rounded-md"
            />
            <div
              v-if="isUploading"
              class="chatify-attachment-progress-overlay chatify:rounded-md"
            >
              <div class="chatify-attachment-progress-ring">{{ uploadProgress }}%</div>
              <button
                type="button"
                class="chatify-attachment-progress-cancel"
                aria-label="Cancel upload"
                @click="emit('cancel')"
              >
                ✕
              </button>
            </div>
          </div>

          <div
            v-else-if="message.attributes.attachment?.type === 'image'"
            class="chatify:relative chatify:mb-1 chatify:inline-block chatify:overflow-hidden chatify:rounded-md"
          >
            <div
              v-if="!imageLoaded"
              class="chatify-attachment-skeleton chatify:h-40 chatify:w-44"
            />
            <button
              type="button"
              class="chatify:block chatify:overflow-hidden chatify:rounded-md"
              :class="imageLoaded ? '' : 'chatify:hidden'"
              @click="openSingleImage"
            >
              <img
                :src="message.attributes.attachment.url"
                :alt="message.attributes.attachment.original_name ?? 'Attachment'"
                class="chatify:max-h-40 chatify:max-w-[11rem] chatify:object-cover"
                @load="imageLoaded = true"
                @error="imageLoaded = true"
              />
            </button>
            <div
              v-if="isUploading"
              class="chatify-attachment-progress-overlay chatify:rounded-md"
            >
              <div class="chatify-attachment-progress-ring">{{ uploadProgress }}%</div>
              <button
                type="button"
                class="chatify-attachment-progress-cancel"
                aria-label="Cancel upload"
                @click="emit('cancel')"
              >
                ✕
              </button>
            </div>
          </div>

          <MessageDocCard
            v-else-if="message.attributes.attachment"
            :attachment="message.attributes.attachment"
            :uploading="isUploading"
            :progress="uploadProgress"
            @cancel="emit('cancel')"
          />
        </template>

        <MessageBody
          v-if="message.attributes.body"
          :body="message.attributes.body"
        />

        <div class="chatify-message-meta chatify:mt-1 chatify:flex chatify:items-center chatify:justify-end chatify:gap-1">
          <span v-if="message.attributes.edited_at" class="chatify:text-[10px] chatify:italic">edited</span>
          <span class="chatify:text-[10px]">
            {{ formatMessageTime(message.attributes.created_at) }}
          </span>
          <MessageDeliveryStatus
            v-if="isOwn"
            :status="localStatus"
            :read="message.attributes.read"
            :is-own="isOwn"
          />
        </div>
      </div>

      <MessageActionsMenu
        v-if="!isOwn && showActions"
        :is-own="isOwn"
        @edit="emit('edit')"
        @remove-for-me="emit('removeForMe')"
        @remove-for-all="emit('removeForAll')"
        @reply="emit('reply')"
        @forward="emit('forward')"
      />
    </div>

    <div
      v-if="isOwn && localStatus === 'failed'"
      class="chatify:flex chatify:justify-end chatify:gap-2 chatify:px-1"
    >
      <button
        type="button"
        class="chatify:text-xs chatify:font-medium chatify:text-chatify-primary"
        @click="emit('resend')"
      >
        Resend
      </button>
      <button
        type="button"
        class="chatify:text-xs chatify:font-medium chatify:text-chatify-muted"
        @click="emit('cancel')"
      >
        Cancel
      </button>
    </div>
  </div>
</template>
