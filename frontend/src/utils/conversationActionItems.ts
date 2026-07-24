import { chatifyT } from '../i18n/nonComponent'
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

const ACTION_LABEL_KEYS: Record<ConversationActionId, string> = {
  pin: 'ui.actions.conversation.pin',
  unpin: 'ui.actions.conversation.unpin',
  markRead: 'ui.actions.conversation.mark_read',
  contactInfo: 'ui.actions.conversation.contact_info',
  groupInfo: 'ui.actions.conversation.group_info',
  favorite: 'ui.actions.conversation.favorite',
  unfavorite: 'ui.actions.conversation.unfavorite',
  block: 'ui.actions.conversation.block',
  unblock: 'ui.actions.conversation.unblock',
  leaveGroup: 'ui.actions.conversation.leave_group',
  deleteGroup: 'ui.actions.conversation.delete_group',
  hideConversation: 'ui.actions.conversation.delete_conversation',
  clearSavedMessages: 'ui.actions.conversation.clear_saved',
}

function actionLabel(id: ConversationActionId): string {
  return chatifyT(ACTION_LABEL_KEYS[id])
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
      label: actionLabel('clearSavedMessages'),
      danger: true,
    })

    return items
  }

  if (!isMessagingBlocked) {
    items.push({
      id: conversation.attributes.is_pinned ? 'unpin' : 'pin',
      label: actionLabel(conversation.attributes.is_pinned ? 'unpin' : 'pin'),
    })
  }

  if (conversation.attributes.unread_count > 0) {
    items.push({
      id: 'markRead',
      label: actionLabel('markRead'),
      separatorBefore: items.length > 0,
    })
  }

  if (isDirect && conversation.relationships.other_user) {
    if (!isMessagingBlocked) {
      items.push({
        id: 'contactInfo',
        label: actionLabel('contactInfo'),
        separatorBefore: items.length > 0,
      })
      items.push({
        id: options.isFavorite ? 'unfavorite' : 'favorite',
        label: actionLabel(options.isFavorite ? 'unfavorite' : 'favorite'),
      })
    }
    items.push({
      id: options.isBlocked ? 'unblock' : 'block',
      label: actionLabel(options.isBlocked ? 'unblock' : 'block'),
      danger: !options.isBlocked,
      separatorBefore: items.length > 0,
    })
    items.push({
      id: 'hideConversation',
      label: actionLabel('hideConversation'),
      danger: true,
      separatorBefore: true,
    })
  }

  if (isGroup) {
    items.push({
      id: 'groupInfo',
      label: actionLabel('groupInfo'),
      separatorBefore: items.length > 0,
    })

    if (conversation.attributes.is_owner) {
      items.push({
        id: 'deleteGroup',
        label: actionLabel('deleteGroup'),
        danger: true,
        separatorBefore: true,
      })
    } else {
      items.push({
        id: 'leaveGroup',
        label: actionLabel('leaveGroup'),
        danger: true,
        separatorBefore: true,
      })
    }
  }

  return items
}
