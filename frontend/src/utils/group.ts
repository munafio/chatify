import { getBootLocale } from '../i18n/bootLocale'
import { chatifyT } from '../i18n/nonComponent'
import type { ChatifyConversation, ChatifyParticipant, GroupMembership, GroupPermissionKey } from '../types'

export function memberCountLabel(count: number | undefined): string {
  const total = count ?? 0
  const key = total === 1 ? 'ui.format.member_count' : 'ui.format.member_count_plural'
  return chatifyT(key, { n: total })
}

export function participantUser(participant: ChatifyParticipant) {
  return participant.relationships.user
}

export function memberRoleLabel(role: string, isFullAdmin = true): string | null {
  switch (role) {
    case 'owner':
      return chatifyT('roles.owner')
    case 'admin':
      return isFullAdmin ? chatifyT('roles.admin') : chatifyT('roles.limited_admin')
    case 'moderator':
      return chatifyT('roles.moderator')
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
  const creator = conversation.attributes.created_by?.name ?? chatifyT('roles.unknown')
  const createdAt = conversation.attributes.created_at

  if (!createdAt) {
    return chatifyT('ui.format.group_created_by', { creator })
  }

  const date = new Date(createdAt)
  const locale = getBootLocale()

  return chatifyT('ui.format.group_created_by_on', {
    creator,
    date: date.toLocaleDateString(locale, {
      month: 'numeric',
      day: 'numeric',
      year: 'numeric',
    }),
    time: date.toLocaleTimeString(locale, { hour: 'numeric', minute: '2-digit' }),
  })
}

export function isParticipantRecord(
  item: ChatifyConversation['relationships']['participants'][number],
): item is ChatifyParticipant {
  return item.type === 'participant'
}
