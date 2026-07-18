import { computed, type Ref } from 'vue'
import type { ChatifyConversation } from '../types'
import { hasGroupPermission } from '../utils/group'

export function useGroupPermissions(conversation: Ref<ChatifyConversation | null>) {
  const membership = computed(() => conversation.value?.attributes.my_membership ?? null)

  const isOwner = computed(() => membership.value?.role === 'owner')
  const canEditInfo = computed(() => hasGroupPermission(membership.value, 'edit_info'))
  const canAddMembers = computed(() => hasGroupPermission(membership.value, 'add_members'))
  const canRemoveMembers = computed(() => hasGroupPermission(membership.value, 'remove_members'))
  const canManageAdmins = computed(() => hasGroupPermission(membership.value, 'manage_admins'))
  const canDeleteGroup = computed(() => isOwner.value)
  const canTransferOwnership = computed(() => isOwner.value)
  const canLeave = computed(() => !isOwner.value)

  return {
    membership,
    isOwner,
    canEditInfo,
    canAddMembers,
    canRemoveMembers,
    canManageAdmins,
    canDeleteGroup,
    canTransferOwnership,
    canLeave,
  }
}
