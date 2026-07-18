import { describe, expect, it } from 'vitest'
import type { ChatifyMessage } from '../types'
import { buildMessageListItems } from './groupMessageClusters'

function makeMessage(
  id: string,
  senderId: number | string,
  createdAt: string,
): ChatifyMessage {
  return {
    type: 'message',
    id,
    attributes: {
      conversation_id: 'conv-1',
      body: 'Hello',
      attachment: null,
      read: false,
      created_at: createdAt,
      updated_at: createdAt,
    },
    relationships: {
      sender: { data: { type: 'user', id: senderId } },
    },
  }
}

describe('buildMessageListItems', () => {
  it('groups messages into day sections with sticky date labels', () => {
    const items = buildMessageListItems(
      [
        makeMessage('m1', 2, '2026-01-01T10:00:00Z'),
        makeMessage('m2', 2, '2026-01-01T10:01:00Z'),
        makeMessage('m3', 1, '2026-01-02T10:00:00Z'),
      ],
      1,
      true,
    )

    expect(items.every((item) => item.kind === 'day')).toBe(true)
    expect(items).toHaveLength(2)

    const firstDay = items[0]
    expect(firstDay?.kind).toBe('day')
    if (firstDay?.kind !== 'day') {
      return
    }

    const cluster = firstDay.items.find(
      (item): item is Extract<(typeof firstDay.items)[number], { kind: 'cluster' }> =>
        item.kind === 'cluster',
    )

    expect(cluster).toBeDefined()
    expect(cluster?.senderId).toBe('2')
    expect(cluster?.entries).toHaveLength(2)
    expect(cluster?.entries[0]?.showSenderName).toBe(true)
    expect(cluster?.entries[1]?.showSenderName).toBe(false)
  })

  it('keeps consecutive incoming messages in one cluster even across long gaps', () => {
    const items = buildMessageListItems(
      [
        makeMessage('m1', 2, '2026-01-01T10:07:00Z'),
        makeMessage('m2', 2, '2026-01-01T10:09:00Z'),
        makeMessage('m3', 2, '2026-01-01T10:13:00Z'),
        makeMessage('m4', 2, '2026-01-01T10:46:00Z'),
      ],
      1,
      true,
    )

    expect(items).toHaveLength(1)
    const day = items[0]
    expect(day?.kind).toBe('day')
    if (day?.kind !== 'day') {
      return
    }

    const clusters = day.items.filter(
      (item): item is Extract<(typeof day.items)[number], { kind: 'cluster' }> =>
        item.kind === 'cluster',
    )

    expect(clusters).toHaveLength(1)
    expect(clusters[0]?.entries).toHaveLength(4)
    expect(clusters[0]?.entries.filter((entry) => entry.showSenderName)).toHaveLength(1)
  })

  it('renders own messages as individual items without sender name', () => {
    const items = buildMessageListItems(
      [
        makeMessage('m1', 1, '2026-01-01T10:00:00Z'),
        makeMessage('m2', 1, '2026-01-01T10:01:00Z'),
      ],
      1,
      true,
    )

    const day = items[0]
    expect(day?.kind).toBe('day')
    if (day?.kind !== 'day') {
      return
    }

    const ownMessages = day.items.filter(
      (item): item is Extract<(typeof day.items)[number], { kind: 'message' }> =>
        item.kind === 'message',
    )

    expect(day.items.some((item) => item.kind === 'cluster')).toBe(false)
    expect(ownMessages).toHaveLength(2)
    expect(ownMessages.every((item) => !item.showSenderName)).toBe(true)
  })

  it('starts a new cluster when senders change', () => {
    const items = buildMessageListItems(
      [
        makeMessage('m1', 2, '2026-01-01T10:00:00Z'),
        makeMessage('m2', 3, '2026-01-01T10:01:00Z'),
      ],
      1,
      true,
    )

    const day = items[0]
    expect(day?.kind).toBe('day')
    if (day?.kind !== 'day') {
      return
    }

    const clusters = day.items.filter(
      (item): item is Extract<(typeof day.items)[number], { kind: 'cluster' }> =>
        item.kind === 'cluster',
    )

    expect(clusters).toHaveLength(2)
    expect(clusters[0]?.senderId).toBe('2')
    expect(clusters[1]?.senderId).toBe('3')
    expect(clusters.every((cluster) => cluster.entries.length === 1)).toBe(true)
  })
})
