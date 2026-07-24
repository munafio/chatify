import { chatifyT } from '../i18n/nonComponent'
import type { ChatifyConversation, ChatifyUser } from '../types'

export function hiddenUserName(): string {
  return chatifyT('ui.user.unknown_user')
}

/** @deprecated Use hiddenUserName() for translated labels */
export const HIDDEN_USER_NAME = 'Unknown User'

export function displayUserName(user: Pick<ChatifyUser, 'attributes'> | null | undefined): string {
  if (!user) {
    return hiddenUserName()
  }

  if (user.attributes.is_identity_hidden) {
    return hiddenUserName()
  }

  return user.attributes.name
}

export function displayUserAvatar(
  user: Pick<ChatifyUser, 'attributes'> | null | undefined,
  defaultAvatarUrl: string,
): string {
  if (!user || user.attributes.is_identity_hidden) {
    return defaultAvatarUrl
  }

  return user.attributes.avatar
}

export function isIdentityHidden(user: Pick<ChatifyUser, 'attributes'> | null | undefined): boolean {
  return Boolean(user?.attributes.is_identity_hidden)
}

export function maskUserIdentity(user: ChatifyUser, defaultAvatarUrl: string): ChatifyUser {
  return {
    ...user,
    attributes: {
      ...user.attributes,
      name: hiddenUserName(),
      avatar: defaultAvatarUrl,
      is_identity_hidden: true,
      email: undefined,
      is_online: undefined,
    },
  }
}

export function sanitizeConversationIdentity(
  conversation: ChatifyConversation,
  options: {
    defaultAvatarUrl: string
    isBlockedByMe: (userId: number | string) => boolean
    isMessagingBlocked: (userId: number | string) => boolean
  },
): ChatifyConversation {
  if (conversation.attributes.conversation_type !== 'direct') {
    return conversation
  }

  const otherUser = conversation.relationships.other_user

  if (
    !otherUser
    || options.isBlockedByMe(otherUser.id)
    || !options.isMessagingBlocked(otherUser.id)
  ) {
    return conversation
  }

  return {
    ...conversation,
    relationships: {
      ...conversation.relationships,
      other_user: maskUserIdentity(otherUser, options.defaultAvatarUrl),
    },
  }
}
