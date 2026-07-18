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

export function buildMessageActionItems(options: {
  isOwn: boolean
  hasCopyableText: boolean
}): MessageActionItem[] {
  const items: MessageActionItem[] = []

  if (options.hasCopyableText) {
    items.push({ id: 'copy', label: 'Copy text' })
  }

  items.push(
    { id: 'reply', label: 'Reply', separatorBefore: items.length > 0 },
    { id: 'forward', label: 'Forward' },
  )

  if (options.isOwn) {
    items.push({ id: 'edit', label: 'Edit' })
  }

  items.push({
    id: 'removeForMe',
    label: 'Remove for me',
    separatorBefore: true,
  })

  if (options.isOwn) {
    items.push({
      id: 'removeForAll',
      label: 'Remove for everyone',
      danger: true,
    })
  }

  return items
}
