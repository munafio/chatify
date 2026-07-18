import type {
  ChatifyMessage,
  MessageClusterEntry,
  MessageListItem,
  MessageThreadEntry,
} from '../types'
import { formatMessageDate } from './format'
import { isSystemMessage } from './systemMessage'

const CLUSTER_GAP_MS = 5 * 60 * 1000

function isSameCluster(previous: ChatifyMessage, current: ChatifyMessage): boolean {
  if (isSystemMessage(previous) || isSystemMessage(current)) {
    return false
  }

  const previousSender = String(previous.relationships.sender.data.id)
  const currentSender = String(current.relationships.sender.data.id)

  return previousSender === currentSender
}

function spacingFor(cluster: ChatifyMessage[], index: number): 'tight' | 'normal' {
  if (index === 0) {
    return 'normal'
  }

  const previousTime = cluster[index - 1].attributes.created_at
    ? new Date(cluster[index - 1].attributes.created_at as string).getTime()
    : 0
  const currentTime = cluster[index].attributes.created_at
    ? new Date(cluster[index].attributes.created_at as string).getTime()
    : 0

  return Math.abs(currentTime - previousTime) > CLUSTER_GAP_MS ? 'normal' : 'tight'
}

function annotateCluster(
  cluster: ChatifyMessage[],
  currentUserId: number | string | undefined,
  isGroup: boolean,
): MessageThreadEntry[] {
  const senderId = String(cluster[0].relationships.sender.data.id)
  const isOwn = currentUserId !== undefined && senderId === String(currentUserId)

  if (isOwn) {
    return cluster.map((message, index) => ({
      kind: 'message' as const,
      key: message.id,
      message,
      clusterSpacing: spacingFor(cluster, index),
    }))
  }

  const entries: MessageClusterEntry[] = cluster.map((message, index) => ({
    message,
    clusterSpacing: spacingFor(cluster, index),
    showSenderName: isGroup && index === 0,
  }))

  return [
    {
      kind: 'cluster' as const,
      key: `cluster-${cluster[0].id}`,
      senderId,
      entries,
    },
  ]
}

function buildThreadEntries(
  messages: ChatifyMessage[],
  currentUserId: number | string | undefined,
  isGroup: boolean,
): MessageThreadEntry[] {
  const result: MessageThreadEntry[] = []
  let cluster: ChatifyMessage[] = []

  function flushCluster() {
    if (cluster.length === 0) {
      return
    }

    result.push(...annotateCluster(cluster, currentUserId, isGroup))
    cluster = []
  }

  for (const message of messages) {
    if (isSystemMessage(message)) {
      flushCluster()
      result.push({
        kind: 'message',
        key: message.id,
        message,
        clusterSpacing: 'normal',
      })
      continue
    }

    if (cluster.length === 0) {
      cluster = [message]
      continue
    }

    if (isSameCluster(cluster[cluster.length - 1], message)) {
      cluster.push(message)
    } else {
      flushCluster()
      cluster = [message]
    }
  }

  flushCluster()
  return result
}

export function buildMessageListItems(
  messages: ChatifyMessage[],
  currentUserId: number | string | undefined,
  isGroup: boolean,
): MessageListItem[] {
  const byDate = new Map<string, ChatifyMessage[]>()

  for (const message of messages) {
    const label = formatMessageDate(message.attributes.created_at)
    const bucket = byDate.get(label)
    if (bucket) {
      bucket.push(message)
    } else {
      byDate.set(label, [message])
    }
  }

  const result: MessageListItem[] = []

  for (const [label, dayMessages] of byDate) {
    result.push({
      kind: 'day',
      key: `day-${label}-${dayMessages[0].id}`,
      label,
      items: buildThreadEntries(dayMessages, currentUserId, isGroup),
    })
  }

  return result
}

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
