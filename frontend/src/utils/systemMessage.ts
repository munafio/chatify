import { chatifyT } from '../i18n/nonComponent'
import type { ChatifyMessage } from '../types'

export type SystemMessageEvent = 'participant_added' | 'participant_removed' | 'participant_left'

export interface SystemMessageEventPayload {
  event: SystemMessageEvent
  actor_user_id: number | string
  target_user_ids: Array<number | string>
}

export function isSystemMessage(message: ChatifyMessage): boolean {
  return message.attributes.kind === 'system'
}

function displayName(
  userId: number | string,
  nameFor: (id: string) => string | undefined,
  currentUserId?: number | string,
): string {
  if (currentUserId !== undefined && String(userId) === String(currentUserId)) {
    return chatifyT('system_messages.you')
  }

  return nameFor(String(userId)) ?? chatifyT('system_messages.member')
}

function joinNames(names: string[]): string {
  if (names.length === 0) {
    return chatifyT('system_messages.members')
  }

  if (names.length === 1) {
    return names[0]
  }

  if (names.length === 2) {
    return chatifyT('system_messages.list_and', { names: names[0], last: names[1] })
  }

  return chatifyT('system_messages.list_comma', {
    names: names.slice(0, -1).join(', '),
    last: names[names.length - 1],
  })
}

export function formatSystemMessage(
  message: ChatifyMessage,
  nameFor: (userId: string) => string | undefined,
  currentUserId?: number | string,
): string {
  const event = message.attributes.system_event as SystemMessageEventPayload | null | undefined

  if (!event) {
    return message.attributes.body ?? ''
  }

  const actorName = displayName(event.actor_user_id, nameFor, currentUserId)
  const targetNames = event.target_user_ids.map((id) => displayName(id, nameFor, currentUserId))
  const targets = joinNames(targetNames)
  const youLabel = chatifyT('system_messages.you')

  switch (event.event) {
    case 'participant_added':
      return chatifyT('system_messages.participant_added', { actor: actorName, targets })
    case 'participant_removed':
      return chatifyT('system_messages.participant_removed', { actor: actorName, target: targets })
    case 'participant_left':
      return chatifyT('system_messages.participant_left', {
        user: targetNames[0] === youLabel ? youLabel : targets,
      })
    default:
      return message.attributes.body ?? ''
  }
}
