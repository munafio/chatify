import type { ChatifyConversation } from '../types'

export type ConversationActionId =
  | 'pin'
  | 'unpin'
  | 'markRead'
  | 'contactInfo'
  | 'groupInfo'
  | 'favorite'
  | 'unfavorite'
  | 'block'
  | 'unblock'
  | 'leaveGroup'
  | 'deleteGroup'
  | 'hideConversation'
  | 'clearSavedMessages'

export interface ConversationActionItem {
  id: ConversationActionId
  label: string
  danger?: boolean
  separatorBefore?: boolean
}

export function buildConversationActionItems(options: {
  conversation: ChatifyConversation
  isFavorite: boolean
  isBlocked: boolean
  isMessagingBlocked?: boolean
}): ConversationActionItem[] {
  const items: ConversationActionItem[] = []
  const { conversation } = options
  const isDirect = conversation.attributes.conversation_type === 'direct'
  const isGroup = conversation.attributes.conversation_type === 'group'
  const isSaved = conversation.attributes.conversation_type === 'saved'
  const isMessagingBlocked = options.isMessagingBlocked ?? false

  if (isSaved) {
    items.push({
      id: 'clearSavedMessages',
      label: 'Clear chat',
      danger: true,
    })

    return items
  }

  if (!isMessagingBlocked) {
    items.push({
      id: conversation.attributes.is_pinned ? 'unpin' : 'pin',
      label: conversation.attributes.is_pinned ? 'Unpin' : 'Pin',
    })
  }

  if (conversation.attributes.unread_count > 0) {
    items.push({
      id: 'markRead',
      label: 'Mark as read',
      separatorBefore: items.length > 0,
    })
  }

  if (isDirect && conversation.relationships.other_user) {
    if (!isMessagingBlocked) {
      items.push({
        id: 'contactInfo',
        label: 'Contact info',
        separatorBefore: items.length > 0,
      })
      items.push({
        id: options.isFavorite ? 'unfavorite' : 'favorite',
        label: options.isFavorite ? 'Unfavorite' : 'Favorite',
      })
    }
    items.push({
      id: options.isBlocked ? 'unblock' : 'block',
      label: options.isBlocked ? 'Unblock' : 'Block',
      danger: !options.isBlocked,
      separatorBefore: items.length > 0,
    })
    items.push({
      id: 'hideConversation',
      label: 'Delete conversation',
      danger: true,
      separatorBefore: true,
    })
  }

  if (isGroup) {
    items.push({
      id: 'groupInfo',
      label: 'Group info',
      separatorBefore: items.length > 0,
    })

    if (conversation.attributes.is_owner) {
      items.push({
        id: 'deleteGroup',
        label: 'Delete group',
        danger: true,
        separatorBefore: true,
      })
    } else {
      items.push({
        id: 'leaveGroup',
        label: 'Leave group',
        danger: true,
        separatorBefore: true,
      })
    }
  }

  return items
}
