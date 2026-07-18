import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ChatifyUser, UserBlockChangedPayload } from '../types'
import { useConfigStore } from './config'
import { useConversationsStore } from './conversations'
import { useMessagesStore } from './messages'

export const useContactsStore = defineStore('contacts', () => {
  const searchResults = ref<ChatifyUser[]>([])
  const favorites = ref<ChatifyUser[]>([])
  const blockedUsers = ref<ChatifyUser[]>([])
  const messagingBlockedUserIds = ref<Set<string>>(new Set())
  const searching = ref(false)
  const searchError = ref<string | null>(null)
  const favoritesLoadFailed = ref(false)
  const blockedLoadFailed = ref(false)
  const blockedLoading = ref(false)

  const configStore = useConfigStore()

  function setMessagingBlockedUserIds(ids: Array<number | string>) {
    messagingBlockedUserIds.value = new Set(ids.map(String))
  }

  function applyMessagingBlockedMeta(meta: { messaging_blocked_user_ids?: Array<number | string> } | undefined) {
    if (meta?.messaging_blocked_user_ids) {
      setMessagingBlockedUserIds(meta.messaging_blocked_user_ids)
    }
  }

  async function search(q: string) {
    if (!configStore.api || !q.trim()) {
      searchResults.value = []
      return
    }

    searching.value = true
    searchError.value = null

    try {
      const { data } = await configStore.api.searchContacts(q.trim())
      searchResults.value = data.data.filter((user) => !isMessagingBlocked(user.id))
    } catch (err) {
      searchError.value = err instanceof Error ? err.message : 'Search failed'
      searchResults.value = []
    } finally {
      searching.value = false
    }
  }

  async function fetchFavorites() {
    if (!configStore.api) {
      return
    }

    favoritesLoadFailed.value = false

    try {
      const { data } = await configStore.api.getFavorites()
      favorites.value = data.data
    } catch {
      favoritesLoadFailed.value = true
    }
  }

  async function fetchBlocked() {
    if (!configStore.api) {
      return
    }

    blockedLoading.value = true
    blockedLoadFailed.value = false

    try {
      const { data } = await configStore.api.getBlockedUsers()
      blockedUsers.value = data.data
      applyMessagingBlockedMeta(data.meta as { messaging_blocked_user_ids?: Array<number | string> } | undefined)
    } catch {
      blockedLoadFailed.value = true
    } finally {
      blockedLoading.value = false
    }
  }

  async function toggleFavorite(userId: number | string) {
    if (!configStore.api) {
      return
    }

    await configStore.api.toggleFavorite(userId)
    await fetchFavorites()
  }

  async function blockUser(userId: number | string) {
    if (!configStore.api) {
      return
    }

    await configStore.api.blockUser(userId)
    await Promise.all([fetchBlocked(), fetchFavorites()])
  }

  async function unblockUser(userId: number | string) {
    if (!configStore.api) {
      return
    }

    await configStore.api.unblockUser(userId)
    await fetchBlocked()
  }

  function isFavorite(userId: number | string): boolean {
    return favorites.value.some((user) => String(user.id) === String(userId))
  }

  function isBlocked(userId: number | string): boolean {
    return blockedUsers.value.some((user) => String(user.id) === String(userId))
  }

  function isBlockedByMe(userId: number | string): boolean {
    return isBlocked(userId)
  }

  function isMessagingBlocked(userId: number | string): boolean {
    return messagingBlockedUserIds.value.has(String(userId))
  }

  function handleUserBlockChanged(payload: UserBlockChangedPayload, currentUserId: number | string) {
    setMessagingBlockedUserIds(payload.messaging_blocked_user_ids)

    const conversationsStore = useConversationsStore()
    const messagesStore = useMessagesStore()

    if (payload.blocked) {
      conversationsStore.resanitizeAll()
      messagesStore.syncBlockFilters(false)
    } else {
      void conversationsStore.fetchAll().then(() => {
        messagesStore.syncBlockFilters(true)
      })
    }

    if (String(currentUserId) === String(payload.blocker_id)) {
      favorites.value = favorites.value.filter(
        (user) => String(user.id) !== String(payload.blocked_user_id),
      )
    }
  }

  return {
    searchResults,
    favorites,
    blockedUsers,
    messagingBlockedUserIds,
    searching,
    searchError,
    favoritesLoadFailed,
    blockedLoadFailed,
    blockedLoading,
    setMessagingBlockedUserIds,
    search,
    fetchFavorites,
    fetchBlocked,
    toggleFavorite,
    blockUser,
    unblockUser,
    isFavorite,
    isBlocked,
    isBlockedByMe,
    isMessagingBlocked,
    handleUserBlockChanged,
  }
})
