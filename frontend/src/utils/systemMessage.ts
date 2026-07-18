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
    return 'You'
  }

  return nameFor(String(userId)) ?? 'Member'
}

function joinNames(names: string[]): string {
  if (names.length === 0) {
    return 'members'
  }

  if (names.length === 1) {
    return names[0]
  }

  if (names.length === 2) {
    return `${names[0]} and ${names[1]}`
  }

  return `${names.slice(0, -1).join(', ')} and ${names[names.length - 1]}`
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

  switch (event.event) {
    case 'participant_added':
      return `${actorName} added ${targets}`
    case 'participant_removed':
      return `${actorName} removed ${targets}`
    case 'participant_left':
      return targetNames[0] === 'You' ? 'You left' : `${targets} left`
    default:
      return message.attributes.body ?? ''
  }
}
