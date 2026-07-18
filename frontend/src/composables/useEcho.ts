import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { onUnmounted } from 'vue'
import type { BootConfig } from '../types'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

export function createEcho(config: BootConfig): Echo<'pusher'> | null {
  const { broadcast, broadcastAuthUrl, csrfToken } = config

  if (!broadcast.key || broadcast.driver === 'null' || broadcast.driver === 'log') {
    return null
  }

  window.Pusher = Pusher

  return new Echo({
    broadcaster: 'pusher',
    key: broadcast.key,
    cluster: broadcast.cluster ?? 'mt1',
    wsHost: broadcast.wsHost ?? undefined,
    wsPort: broadcast.wsPort,
    wssPort: broadcast.wsPort,
    forceTLS: broadcast.forceTLS,
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel: { name: string }) => ({
      authorize: (socketId: string, callback: (error: Error | null, data: unknown) => void) => {
        fetch(broadcastAuthUrl, {
          method: 'POST',
          credentials: 'include',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            socket_id: socketId,
            channel_name: channel.name,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error('Broadcast auth failed')
            }
            return response.json()
          })
          .then((data) => callback(null, data))
          .catch((error) => callback(error instanceof Error ? error : new Error(String(error)), null))
      },
    }),
  })
}

export function useEcho(config: BootConfig) {
  const echo = createEcho(config)

  onUnmounted(() => {
    echo?.disconnect()
  })

  return echo
}

export function subscribeToUserInbox(
  echo: Echo<'pusher'> | null,
  userId: number | string,
  handlers: {
    onInboxUpdated?: (payload: unknown) => void
    onGroupMembershipRevoked?: (payload: unknown) => void
  },
) {
  if (!echo) {
    return () => {}
  }

  const channel = echo.private(`chatify.user.${userId}`)

  if (handlers.onInboxUpdated) {
    channel.listen('.InboxUpdated', handlers.onInboxUpdated)
  }
  if (handlers.onGroupMembershipRevoked) {
    channel.listen('.GroupMembershipRevoked', handlers.onGroupMembershipRevoked)
  }

  return () => {
    echo.leave(`chatify.user.${userId}`)
  }
}

export function subscribeToConversation(
  echo: Echo<'pusher'> | null,
  conversationId: string,
  handlers: {
    onMessageSent?: (payload: unknown) => void
    onMessageUpdated?: (payload: unknown) => void
    onMessageDeleted?: (payload: unknown) => void
    onConversationRead?: (payload: unknown) => void
    onGroupParticipantsChanged?: (payload: unknown) => void
    onUserTyping?: (payload: unknown) => void
  },
) {
  if (!echo) {
    return () => {}
  }

  const channel = echo.private(`chatify.conversation.${conversationId}`)

  if (handlers.onMessageSent) {
    channel.listen('.MessageSent', handlers.onMessageSent)
  }
  if (handlers.onMessageUpdated) {
    channel.listen('.MessageUpdated', handlers.onMessageUpdated)
  }
  if (handlers.onMessageDeleted) {
    channel.listen('.MessageDeleted', handlers.onMessageDeleted)
  }
  if (handlers.onConversationRead) {
    channel.listen('.ConversationRead', handlers.onConversationRead)
  }
  if (handlers.onGroupParticipantsChanged) {
    channel.listen('.GroupParticipantsChanged', handlers.onGroupParticipantsChanged)
  }
  if (handlers.onUserTyping) {
    channel.listen('.UserTyping', handlers.onUserTyping)
  }

  return () => {
    echo.leave(`chatify.conversation.${conversationId}`)
  }
}

export function bindEchoReconnect(echo: Echo<'pusher'> | null, onReconnect: () => void) {
  if (!echo) {
    return () => {}
  }

  const connector = (echo as Echo<'pusher'> & { connector?: { pusher?: { connection: { bind: Function; unbind: Function } } } }).connector
  const connection = connector?.pusher?.connection

  if (!connection) {
    return () => {}
  }

  const handler = (state: { previous: string; current: string }) => {
    if (state.current === 'connected' && state.previous !== 'connected') {
      onReconnect()
    }
  }

  connection.bind('state_change', handler)

  return () => {
    connection.unbind('state_change', handler)
  }
}
