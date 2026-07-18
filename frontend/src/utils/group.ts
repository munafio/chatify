import type { ChatifyConversation, ChatifyParticipant, GroupMembership, GroupPermissionKey } from '../types'

export function memberCountLabel(count: number | undefined): string {
  const total = count ?? 0
  return `${total} member${total === 1 ? '' : 's'}`
}

export function participantUser(participant: ChatifyParticipant) {
  return participant.relationships.user
}

export function memberRoleLabel(role: string, isFullAdmin = true): string | null {
  switch (role) {
    case 'owner':
      return 'Owner'
    case 'admin':
      return isFullAdmin ? 'Admin' : 'Limited admin'
    case 'moderator':
      return 'Moderator'
    case 'member':
      return null
    default:
      return null
  }
}

export function memberRoleBadgeClass(role: string): string {
  switch (role) {
    case 'owner':
      return 'chatify-member-role-badge chatify-member-role-badge-owner'
    case 'admin':
      return 'chatify-member-role-badge chatify-member-role-badge-admin'
    case 'moderator':
      return 'chatify-member-role-badge chatify-member-role-badge-moderator'
    case 'member':
      return 'chatify-member-role-badge chatify-member-role-badge-member'
    default:
      return 'chatify-member-role-badge'
  }
}

export function hasGroupPermission(
  membership: GroupMembership | null | undefined,
  permission: GroupPermissionKey,
): boolean {
  if (!membership) {
    return false
  }

  if (membership.role === 'owner') {
    return true
  }

  if (membership.role === 'moderator') {
    return permission === 'add_members' || permission === 'remove_members'
  }

  if (membership.role === 'admin') {
    if (membership.is_full_admin || membership.permissions === null) {
      return true
    }

    return Boolean(membership.permissions?.[permission])
  }

  return false
}

export function formatGroupCreatedFooter(conversation: ChatifyConversation): string {
  const creator = conversation.attributes.created_by?.name ?? 'Unknown'
  const createdAt = conversation.attributes.created_at

  if (!createdAt) {
    return `Group created by ${creator}`
  }

  const date = new Date(createdAt)
  return `Group created by ${creator}, on ${date.toLocaleDateString([], {
    month: 'numeric',
    day: 'numeric',
    year: 'numeric',
  })} at ${date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`
}

export function isParticipantRecord(
  item: ChatifyConversation['relationships']['participants'][number],
): item is ChatifyParticipant {
  return item.type === 'participant'
}
