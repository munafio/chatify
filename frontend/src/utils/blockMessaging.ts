import type { ChatifyMessage } from '../types'

export function isBlockedSender(
  senderId: number | string | null | undefined,
  isMessagingBlocked: (userId: number | string) => boolean,
): boolean {
  if (senderId === null || senderId === undefined) {
    return false
  }

  return isMessagingBlocked(senderId)
}

export function filterMessagesFromBlockedSenders(
  messages: ChatifyMessage[],
  isMessagingBlocked: (userId: number | string) => boolean,
  viewerId?: number | string,
): ChatifyMessage[] {
  return messages.filter((message) => {
    if (message.attributes.kind === 'system') {
      return true
    }

    const senderId = message.relationships.sender.data.id

    if (viewerId !== undefined && String(senderId) === String(viewerId)) {
      return true
    }

    return !isBlockedSender(senderId, isMessagingBlocked)
  })
}
