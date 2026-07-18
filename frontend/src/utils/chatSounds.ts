import { playChatSound, unlockChatAudio } from '../composables/useChatSounds'
import type { ChatifyMessage } from '../types'

const recentIncomingSoundMessageIds = new Set<string>()
const MAX_RECENT_INCOMING_SOUND_IDS = 200

export function resolveMessageSenderId(message: ChatifyMessage | null | undefined): string | null {
  const senderId = message?.relationships?.sender?.data?.id
  return senderId == null ? null : String(senderId)
}

export function shouldPlayIncomingSound(options: {
  conversationId: string
  senderId: number | string
  currentUserId: number | string
  activeConversationId: string | null
  isSenderBlocked: boolean
}): boolean {
  if (String(options.senderId) === String(options.currentUserId)) {
    return false
  }

  if (options.isSenderBlocked) {
    return false
  }

  const viewingConversation =
    options.activeConversationId !== null
    && String(options.activeConversationId) === String(options.conversationId)
  const tabVisible = typeof document === 'undefined' || document.visibilityState === 'visible'

  if (viewingConversation && tabVisible) {
    return false
  }

  return true
}

export function tryPlayIncomingMessageSound(options: {
  messageId: string
  conversationId: string
  senderId: number | string
  currentUserId: number | string
  activeConversationId: string | null
  isSenderBlocked: boolean
}): void {
  if (!shouldPlayIncomingSound(options)) {
    return
  }

  if (recentIncomingSoundMessageIds.has(options.messageId)) {
    return
  }

  recentIncomingSoundMessageIds.add(options.messageId)

  if (recentIncomingSoundMessageIds.size > MAX_RECENT_INCOMING_SOUND_IDS) {
    const [oldest] = recentIncomingSoundMessageIds
    if (oldest) {
      recentIncomingSoundMessageIds.delete(oldest)
    }
  }

  unlockChatAudio()
  playChatSound('incomingMessage')
}

export function resetIncomingSoundDedupeForTests() {
  recentIncomingSoundMessageIds.clear()
}
