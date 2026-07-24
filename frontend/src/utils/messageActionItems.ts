import { chatifyT } from '../i18n/nonComponent'

export type MessageActionId =
  | 'copy'
  | 'edit'
  | 'reply'
  | 'forward'
  | 'removeForMe'
  | 'removeForAll'

export interface MessageActionItem {
  id: MessageActionId
  label: string
  danger?: boolean
  separatorBefore?: boolean
}

const ACTION_LABEL_KEYS: Record<MessageActionId, string> = {
  copy: 'ui.actions.message.copy_text',
  edit: 'ui.actions.message.edit',
  reply: 'ui.actions.message.reply',
  forward: 'ui.actions.message.forward',
  removeForMe: 'ui.actions.message.remove_for_me',
  removeForAll: 'ui.actions.message.remove_for_everyone',
}

function actionLabel(id: MessageActionId): string {
  return chatifyT(ACTION_LABEL_KEYS[id])
}

export function buildMessageActionItems(options: {
  isOwn: boolean
  hasCopyableText: boolean
  isSavedConversation?: boolean
}): MessageActionItem[] {
  const items: MessageActionItem[] = []

  if (options.hasCopyableText) {
    items.push({ id: 'copy', label: actionLabel('copy') })
  }

  items.push(
    { id: 'reply', label: actionLabel('reply'), separatorBefore: items.length > 0 },
    { id: 'forward', label: actionLabel('forward') },
  )

  if (options.isOwn) {
    items.push({ id: 'edit', label: actionLabel('edit') })
  }

  items.push({
    id: 'removeForMe',
    label: actionLabel('removeForMe'),
    separatorBefore: true,
  })

  if (options.isOwn && !options.isSavedConversation) {
    items.push({
      id: 'removeForAll',
      label: actionLabel('removeForAll'),
      danger: true,
    })
  }

  return items
}
