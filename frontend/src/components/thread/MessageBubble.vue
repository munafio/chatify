<script setup lang="ts">
import { computed } from 'vue'
import type { ChatifyMessage } from '../../types'
import { formatMessageTime } from '../../utils/format'
import { isPendingMessageId } from '../../utils/outboundMessage'
import { bubbleTextClass } from '../../themes/utils'
import { useImageLightbox } from '../../composables/useImageLightbox'
import MessageAlbumBubble from './MessageAlbumBubble.vue'
import MessageActionsMenu from './MessageActionsMenu.vue'
import MessageDeliveryStatus from './MessageDeliveryStatus.vue'

const props = defineProps<{
  message: ChatifyMessage
  isOwn: boolean
  isGroup?: boolean
  senderName?: string
}>()

const emit = defineEmits<{
  edit: []
  removeForMe: []
  removeForAll: []
  reply: []
  forward: []
  resend: []
  cancel: []
}>()

const { show } = useImageLightbox()

const localStatus = computed(() => props.message.attributes.local_status ?? null)
const isPending = computed(() => isPendingMessageId(props.message.id))
const showActions = computed(() => !isPending.value && localStatus.value !== 'sending')

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

function openSingleImage() {
  const attachment = props.message.attributes.attachment
  if (attachment?.type === 'image') {
    show([attachment], 0)
  }
}
</script>

<template>
  <div class="chatify:flex chatify:w-full chatify:flex-col chatify:gap-1">
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
        class="chatify:max-w-[75%] chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:shadow-sm"
        :class="[
          isOwn ? `chatify:bg-chatify-bubble-out ${bubbleTextClass(true)}` : `chatify:bg-chatify-bubble-in ${bubbleTextClass(false)}`,
          localStatus === 'sending' ? 'chatify-message-pending' : '',
          localStatus === 'failed' ? 'chatify-message-failed' : '',
        ]"
      >
        <p
          v-if="isGroup && !isOwn && senderName"
          class="chatify:mb-1 chatify:text-xs chatify:font-semibold chatify:text-chatify-primary-dark"
        >
          {{ senderName }}
        </p>

        <div
          v-if="message.attributes.reply_to"
          class="chatify:mb-2 chatify:border-l-2 chatify:border-chatify-primary chatify:pl-2 chatify:text-xs chatify:opacity-80"
        >
          <p class="chatify:font-semibold">{{ message.attributes.reply_to.sender_name }}</p>
          <p class="chatify:truncate">{{ message.attributes.reply_to.body || 'Attachment' }}</p>
        </div>

        <MessageAlbumBubble
          v-if="isImageAlbum()"
          :attachments="attachments()"
        />

        <template v-else>
          <button
            v-if="message.attributes.attachment?.type === 'image'"
            type="button"
            class="chatify:mb-1 chatify:block chatify:overflow-hidden chatify:rounded-md"
            @click="openSingleImage"
          >
            <img
              :src="message.attributes.attachment.url"
              :alt="message.attributes.attachment.original_name ?? 'Attachment'"
              class="chatify:max-h-40 chatify:max-w-[11rem] chatify:object-cover"
            />
          </button>

          <a
            v-else-if="message.attributes.attachment"
            :href="message.attributes.attachment.url"
            target="_blank"
            rel="noopener noreferrer"
            class="chatify:mb-1 chatify:block chatify:text-sm chatify:text-chatify-primary chatify:underline"
          >
            {{ message.attributes.attachment.original_name ?? 'Download file' }}
          </a>
        </template>

        <p
          v-if="message.attributes.body"
          class="chatify-message-body chatify:whitespace-pre-wrap chatify:break-words chatify:text-sm"
        >
          {{ message.attributes.body }}
        </p>

        <div class="chatify:mt-1 chatify:flex chatify:items-center chatify:justify-end chatify:gap-1">
          <span v-if="message.attributes.edited_at" class="chatify:text-[10px] chatify:italic chatify:text-chatify-muted">edited</span>
          <span class="chatify:text-[10px] chatify:text-chatify-muted">
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
