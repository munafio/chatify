<script setup lang="ts">
import { computed } from 'vue'
import type { ChatifyConversation, ChatifyParticipant, MessageAttachment, SharedAttachment } from '../../types'
import { memberCountLabel, formatGroupCreatedFooter, memberRoleBadgeClass, memberRoleLabel, participantUser } from '../../utils/group'
import { useImageLightbox } from '../../composables/useImageLightbox'
import GroupAvatar from './GroupAvatar.vue'
import GroupEditFields from './GroupEditFields.vue'

const props = defineProps<{
  conversation: ChatifyConversation
  mediaPreview: SharedAttachment[]
  mediaTotal: number
  membersPreview: ChatifyParticipant[]
  canEditInfo: boolean
  canAddMembers: boolean
  canLeave: boolean
  canDeleteGroup: boolean
  avatarUploading?: boolean
  avatarUploadProgress?: number | null
}>()

const emit = defineEmits<{
  openMembers: [mode: 'browse' | 'add']
  openMedia: []
  openSearch: []
  saveName: [value: string]
  saveDescription: [value: string | null]
  uploadAvatar: [file: File]
  leave: []
  deleteGroup: []
}>()

const { show } = useImageLightbox()

function isImageItem(item: SharedAttachment): boolean {
  const mime = item.attributes.mime ?? ''
  if (mime.startsWith('image/')) {
    return true
  }
  return /\.(png|jpe?g|gif|webp|bmp|svg|avif)$/i.test(item.attributes.url ?? '')
}

const previewImages = computed(() => props.mediaPreview.filter(isImageItem))

function openPreview(item: SharedAttachment) {
  if (!isImageItem(item)) {
    emit('openMedia')
    return
  }

  const gallery = previewImages.value
  const index = gallery.findIndex(
    (candidate) =>
      candidate.attributes.message_id === item.attributes.message_id &&
      candidate.attributes.url === item.attributes.url,
  )

  const attachments: MessageAttachment[] = gallery.map((entry) => ({
    filename: entry.attributes.filename ?? '',
    original_name: entry.attributes.original_name,
    type: 'image',
    url: entry.attributes.url ?? '',
  }))

  show(attachments, Math.max(0, index))
}

function memberDisplayName(participant: ChatifyParticipant): string {
  if (participant.attributes.is_you) {
    return 'You'
  }
  return participantUser(participant)?.attributes.name ?? 'Member'
}
</script>

<template>
  <div class="chatify:space-y-4 chatify:pb-2">
    <GroupAvatar
      :src="conversation.attributes.avatar_url"
      :name="conversation.attributes.name ?? 'Group'"
      :editable="canEditInfo"
      :uploading="avatarUploading"
      :upload-progress="avatarUploadProgress"
      @upload="emit('uploadAvatar', $event)"
    />

    <GroupEditFields
      :name="conversation.attributes.name ?? ''"
      :description="conversation.attributes.description"
      :editable="canEditInfo"
      @save-name="emit('saveName', $event)"
      @save-description="emit('saveDescription', $event)"
    />

    <p class="chatify:text-center chatify:text-sm chatify:text-chatify-muted">
      Group · <span class="chatify:text-chatify-primary">{{ memberCountLabel(conversation.attributes.participant_count) }}</span>
    </p>

    <div class="chatify:flex chatify:justify-center chatify:gap-6">
      <button
        v-if="canAddMembers"
        type="button"
        class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-1"
        @click="emit('openMembers', 'add')"
      >
        <span class="chatify:flex chatify:h-12 chatify:w-12 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-sidebar">
          <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
          </svg>
        </span>
        <span class="chatify:text-xs">Add members</span>
      </button>
      <button type="button" class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-1" @click="emit('openSearch')">
        <span class="chatify:flex chatify:h-12 chatify:w-12 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-sidebar">
          <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <span class="chatify:text-xs">Search</span>
      </button>
    </div>

    <div class="chatify:space-y-2">
      <button
        type="button"
        class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:justify-between chatify:rounded-lg chatify:px-3 chatify:py-2.5 chatify:text-left"
        :class="'chatify:bg-chatify-sidebar'"
        @click="emit('openMedia')"
      >
        <span class="chatify:flex chatify:items-center chatify:gap-2 chatify:text-sm">
          <svg class="chatify:h-5 chatify:w-5 chatify:text-chatify-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Media, links and docs
        </span>
        <span class="chatify:text-sm chatify:text-chatify-muted">{{ mediaTotal }}</span>
      </button>

      <div v-if="mediaPreview.length" class="chatify:grid chatify:grid-cols-4 chatify:gap-1 chatify:px-1">
        <button
          v-for="item in mediaPreview"
          :key="item.attributes.message_id ?? item.attributes.url ?? ''"
          type="button"
          class="chatify:group chatify:relative chatify:aspect-square chatify:overflow-hidden chatify:rounded-md chatify:bg-chatify-sidebar"
          @click="openPreview(item)"
        >
          <img
            v-if="item.attributes.url"
            :src="item.attributes.url"
            alt=""
            class="chatify:h-full chatify:w-full chatify:object-cover chatify:transition group-hover:chatify:opacity-80"
          />
        </button>
      </div>
    </div>

    <div class="chatify:space-y-1">
      <div class="chatify:flex chatify:items-center chatify:justify-between chatify:px-1 chatify:py-1">
        <p class="chatify:text-sm chatify:text-chatify-muted">
          {{ memberCountLabel(conversation.attributes.participant_count) }}
        </p>
        <button
          type="button"
          class="chatify:rounded-full chatify:p-1.5 chatify:text-chatify-muted chatify:transition chatify:hover:bg-chatify-sidebar"
          aria-label="Search members"
          @click="emit('openMembers', 'browse')"
        >
          <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </button>
      </div>

      <ul>
        <li
          v-for="participant in membersPreview"
          :key="String(participant.id)"
          class="chatify-list-item chatify:flex chatify:items-center chatify:gap-3 chatify:rounded-lg chatify:px-2 chatify:py-2.5"
        >
          <img
            :src="participantUser(participant)?.attributes.avatar"
            :alt="memberDisplayName(participant)"
            class="chatify:h-9 chatify:w-9 chatify:rounded-full chatify:object-cover"
          />
          <div class="chatify:min-w-0 chatify:flex-1">
            <p class="chatify:truncate chatify:text-sm">{{ memberDisplayName(participant) }}</p>
          </div>
          <span
            v-if="memberRoleLabel(participant.attributes.role, participant.attributes.is_full_admin)"
            :class="memberRoleBadgeClass(participant.attributes.role)"
          >
            {{ memberRoleLabel(participant.attributes.role, participant.attributes.is_full_admin) }}
          </span>
        </li>
      </ul>

      <button
        type="button"
        class="chatify:px-2 chatify:py-1 chatify:text-xs chatify:text-chatify-primary"
        @click="emit('openMembers', 'browse')"
      >
        View all
      </button>
    </div>

    <div class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-1 chatify:pt-2">
      <button
        v-if="canLeave"
        type="button"
        class="chatify-btn-ghost-danger chatify-btn-ghost-danger-centered"
        @click="emit('leave')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Leave group
      </button>
      <button
        v-if="canDeleteGroup"
        type="button"
        class="chatify-btn-ghost-danger chatify-btn-ghost-danger-centered"
        @click="emit('deleteGroup')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        Delete group
      </button>
    </div>

    <p class="chatify:text-center chatify:text-xs chatify:text-chatify-muted">
      {{ formatGroupCreatedFooter(conversation) }}
    </p>
  </div>
</template>
