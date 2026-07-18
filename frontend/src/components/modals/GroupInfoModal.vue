<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyConversation, ChatifyParticipant, GroupInfoView, GroupMembersMode, GroupPermissionKey, SharedAttachment } from '../../types'
import { useGroupPermissions } from '../../composables/useGroupPermissions'
import { useConfigStore } from '../../stores/config'
import { useConfirmStore } from '../../stores/confirm'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { memberCountLabel, participantUser } from '../../utils/group'
import { displayUserName } from '../../utils/userDisplay'
import BaseModal from './BaseModal.vue'
import SettingsNavShell from '../settings/SettingsNavShell.vue'
import GroupAdminPermissionsSheet from '../group-info/GroupAdminPermissionsSheet.vue'
import GroupInfoMainView from '../group-info/GroupInfoMainView.vue'
import GroupMediaView from '../group-info/GroupMediaView.vue'
import GroupMembersView from '../group-info/GroupMembersView.vue'
import GroupInfoMainSkeleton from '../skeletons/GroupInfoMainSkeleton.vue'
import GroupMembersSkeleton from '../skeletons/GroupMembersSkeleton.vue'
import GroupMediaSkeleton from '../skeletons/GroupMediaSkeleton.vue'

const uiStore = useUiStore()
const configStore = useConfigStore()
const confirmStore = useConfirmStore()
const conversationsStore = useConversationsStore()
const { activeModal, modalContext } = storeToRefs(uiStore)

const conversation = ref<ChatifyConversation | null>(null)
const mediaPreview = ref<SharedAttachment[]>([])
const mediaTotal = ref(0)
const loading = ref(false)
const direction = ref<'forward' | 'back'>('forward')
const avatarUploadProgress = ref<number | null>(null)

const view = computed<GroupInfoView>(() => (modalContext.value.view as GroupInfoView) ?? 'main')
const membersMode = computed<GroupMembersMode>(() => (modalContext.value.membersMode as GroupMembersMode) ?? 'browse')

const open = computed(() => activeModal.value === 'groupInfo')
const avatarUploading = computed(() => avatarUploadProgress.value !== null)

const permissions = useGroupPermissions(conversation)

const memberIds = computed(() => {
  const ids = new Set<string>()
  conversation.value?.relationships.participants.forEach((item) => {
    ids.add(String(item.id))
  })
  conversation.value?.relationships.participants_preview?.forEach((item) => {
    ids.add(String(item.id))
  })
  return ids
})

const membersPreview = computed(() => conversation.value?.relationships.participants_preview ?? [])

const modalTitle = computed(() => {
  if (view.value === 'members') {
    if (membersMode.value === 'add') {
      return 'Add members'
    }
    return memberCountLabel(conversation.value?.attributes.participant_count)
  }
  if (view.value === 'media') {
    return 'Media, links and docs'
  }
  return 'Group info'
})

const transitionKey = computed(() => {
  if (view.value === 'members') {
    return `members-${membersMode.value}`
  }
  return view.value
})

const adminSheetOpen = ref(false)
const selectedParticipant = ref<ChatifyParticipant | null>(null)
const navShell = ref<InstanceType<typeof SettingsNavShell> | null>(null)

watch(transitionKey, async () => {
  await nextTick()
  navShell.value?.scrollToTop()
})

async function loadDetails() {
  const conversationId = modalContext.value.conversationId as string | undefined
  if (!conversationId || !configStore.api) {
    return
  }

  loading.value = true
  try {
    const [{ data }, attachments] = await Promise.all([
      configStore.api.getConversation(conversationId),
      configStore.api.getAttachments(conversationId, { type: 'media', page: 1, per_page: 4 }),
    ])

    conversation.value = data.data
    conversationsStore.updateGroupDetails(data.data)
    mediaPreview.value = attachments.data.data
    mediaTotal.value = attachments.data.meta?.total_all ?? attachments.data.meta?.total ?? attachments.data.data.length
  } finally {
    loading.value = false
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    direction.value = 'forward'
    void loadDetails()
    return
  }

  conversation.value = null
  avatarUploadProgress.value = null
})

function setView(next: GroupInfoView, mode: GroupMembersMode = 'browse', navDirection: 'forward' | 'back' = 'forward') {
  direction.value = navDirection
  uiStore.openModal('groupInfo', {
    conversationId: modalContext.value.conversationId,
    view: next,
    membersMode: mode,
  })
}

function goBack() {
  if (view.value === 'main') {
    uiStore.closeModal()
    return
  }

  setView('main', 'browse', 'back')
}

function closeModal() {
  uiStore.closeModal()
}

async function saveName(name: string) {
  if (!conversation.value || !configStore.api) {
    return
  }
  const { data } = await configStore.api.updateConversation(conversation.value.id, { name })
  conversation.value = data.data
  conversationsStore.updateGroupDetails(data.data)
}

async function saveDescription(description: string | null) {
  if (!conversation.value || !configStore.api) {
    return
  }
  const { data } = await configStore.api.updateConversation(conversation.value.id, { description })
  conversation.value = data.data
  conversationsStore.updateGroupDetails(data.data)
}

async function uploadAvatar(file: File) {
  if (!conversation.value || !configStore.api || avatarUploading.value) {
    return
  }

  avatarUploadProgress.value = 0
  try {
    const { data } = await configStore.api.uploadGroupAvatar(conversation.value.id, file, {
      onUploadProgress: (event) => {
        if (!event.total) {
          return
        }
        avatarUploadProgress.value = Math.min(100, Math.round((event.loaded / event.total) * 100))
      },
    })
    conversation.value = data.data
    conversationsStore.updateGroupDetails(data.data)
  } finally {
    avatarUploadProgress.value = null
  }
}

async function addMember(userId: number | string) {
  if (!conversation.value || !configStore.api) {
    return
  }
  const { data } = await configStore.api.addParticipants(conversation.value.id, [userId])
  conversation.value = data.data
  conversationsStore.updateGroupDetails(data.data)
}

function memberName(participant: ChatifyParticipant): string {
  return displayUserName(participantUser(participant))
}

async function removeMember(participant: ChatifyParticipant) {
  if (!conversation.value || !configStore.api) {
    return
  }

  const name = memberName(participant)
  const confirmed = await confirmStore.confirm({
    title: `Remove ${name}?`,
    message: "They won't be able to see new messages in this group.",
    confirmLabel: 'Remove',
    variant: 'danger',
  })

  if (!confirmed) {
    return
  }

  await configStore.api.removeParticipant(conversation.value.id, participant.id)
  await loadDetails()
}

function openSearch() {
  uiStore.closeModal()
  uiStore.openMessageSearch()
}

async function leaveGroup() {
  if (!conversation.value || !configStore.api) {
    return
  }

  const confirmed = await confirmStore.confirm({
    title: 'Leave this group?',
    message: "You won't receive new messages from this group.",
    confirmLabel: 'Leave group',
    variant: 'danger',
  })

  if (!confirmed) {
    return
  }

  await configStore.api.leaveGroup(conversation.value.id)
  uiStore.closeModal()
  conversationsStore.clearActive()
  await conversationsStore.fetchAll()
}

async function deleteGroup() {
  if (!conversation.value || !configStore.api) {
    return
  }

  const confirmed = await confirmStore.confirm({
    title: 'Delete group?',
    message: 'This permanently deletes the group for everyone.',
    confirmLabel: 'Delete group',
    variant: 'danger',
  })

  if (!confirmed) {
    return
  }

  await configStore.api.deleteConversation(conversation.value.id)
  uiStore.closeModal()
  conversationsStore.clearActive()
  await conversationsStore.fetchAll()
}

function openMemberAction(participant: ChatifyParticipant) {
  selectedParticipant.value = participant
  adminSheetOpen.value = true
}

async function openTransfer(participant: ChatifyParticipant) {
  selectedParticipant.value = participant
  const name = memberName(participant)

  const confirmed = await confirmStore.confirm({
    title: 'Transfer ownership?',
    message: `Make ${name} the group owner. You will become a full admin and lose delete and ownership transfer powers.`,
    confirmLabel: 'Transfer',
    variant: 'danger',
  })

  if (!confirmed || !conversation.value || !configStore.api) {
    selectedParticipant.value = null
    return
  }

  const { data } = await configStore.api.transferOwnership(conversation.value.id, participant.id)
  conversation.value = data.data
  conversationsStore.updateGroupDetails(data.data)
  selectedParticipant.value = null
}

async function saveMemberRole(payload: {
  role: 'admin' | 'moderator' | 'member'
  permissions: Partial<Record<GroupPermissionKey, boolean>> | null
}) {
  if (!conversation.value || !selectedParticipant.value || !configStore.api) {
    return
  }

  const { data } = await configStore.api.updateMemberRole(
    conversation.value.id,
    selectedParticipant.value.id,
    payload,
  )
  conversation.value = data.data
  conversationsStore.updateGroupDetails(data.data)
  adminSheetOpen.value = false
  selectedParticipant.value = null
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Group info"
    size="md"
    bare
    panel-class="chatify-settings-modal"
    @close="closeModal"
  >
    <SettingsNavShell
      ref="navShell"
      :title="modalTitle"
      :show-back="view !== 'main'"
      :content-scroll="view === 'main'"
      @back="goBack"
      @close="closeModal"
    >
      <Transition :name="direction === 'forward' ? 'chatify-slide-left' : 'chatify-slide-right'" mode="out-in">
        <div
          :key="transitionKey"
          class="chatify:min-h-0 chatify:min-w-0"
          :class="view === 'main' ? '' : 'chatify:flex chatify:h-full chatify:flex-col'"
        >
          <GroupInfoMainSkeleton v-if="view === 'main' && loading && !conversation" />

          <GroupInfoMainView
            v-else-if="view === 'main' && conversation"
            :conversation="conversation"
            :media-preview="mediaPreview"
            :media-total="mediaTotal"
            :members-preview="membersPreview"
            :can-edit-info="permissions.canEditInfo.value"
            :can-add-members="permissions.canAddMembers.value"
            :can-leave="permissions.canLeave.value"
            :can-delete-group="permissions.canDeleteGroup.value"
            :avatar-uploading="avatarUploading"
            :avatar-upload-progress="avatarUploadProgress"
            @open-members="setView('members', $event)"
            @open-media="setView('media')"
            @open-search="openSearch"
            @save-name="saveName"
            @save-description="saveDescription"
            @upload-avatar="uploadAvatar"
            @leave="leaveGroup"
            @delete-group="deleteGroup"
          />

          <GroupMembersSkeleton v-else-if="view === 'members' && !conversation" />

          <GroupMembersView
            v-else-if="view === 'members' && conversation"
            :conversation="conversation"
            :mode="membersMode"
            :can-add-members="permissions.canAddMembers.value"
            :can-remove-members="permissions.canRemoveMembers.value"
            :can-manage-admins="permissions.canManageAdmins.value"
            :can-transfer-ownership="permissions.canTransferOwnership.value"
            :is-owner="permissions.isOwner.value"
            :member-ids="memberIds"
            :add-member-handler="addMember"
            @add-member="setView('members', 'add')"
            @remove="removeMember"
            @member-action="openMemberAction"
            @transfer="openTransfer"
          />

          <GroupMediaSkeleton v-else-if="view === 'media' && !conversation" />

          <GroupMediaView v-else-if="view === 'media' && conversation" :conversation="conversation" />
        </div>
      </Transition>
    </SettingsNavShell>

    <GroupAdminPermissionsSheet
      :open="adminSheetOpen"
      :member-name="selectedParticipant ? memberName(selectedParticipant) : 'Member'"
      :can-promote-full-admin="permissions.isOwner.value"
      @close="adminSheetOpen = false"
      @save="saveMemberRole"
    />
  </BaseModal>
</template>
