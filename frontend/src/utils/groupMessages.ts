import type { ChatifyMessage, MessageListItem } from '../types'
import { formatMessageDate } from './format'

export function groupMessagesByDate(messages: ChatifyMessage[]): MessageListItem[] {
  const items: MessageListItem[] = []
  let lastLabel = ''

  for (const message of messages) {
    const label = formatMessageDate(message.attributes.created_at)
    if (label !== lastLabel) {
      items.push({ kind: 'date', key: `date-${label}-${message.id}`, label })
      lastLabel = label
    }
    items.push({ kind: 'message', key: message.id, message })
  }

  return items
}
