import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ChatifyUser } from '../types'
import { useConfigStore } from './config'

export const useContactsStore = defineStore('contacts', () => {
  const searchResults = ref<ChatifyUser[]>([])
  const favorites = ref<ChatifyUser[]>([])
  const searching = ref(false)
  const searchError = ref<string | null>(null)

  const configStore = useConfigStore()

  async function search(q: string) {
    if (!configStore.api || !q.trim()) {
      searchResults.value = []
      return
    }

    searching.value = true
    searchError.value = null

    try {
      const { data } = await configStore.api.searchContacts(q.trim())
      searchResults.value = data.data
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

    const { data } = await configStore.api.getFavorites()
    favorites.value = data.data
  }

  async function toggleFavorite(userId: number | string) {
    if (!configStore.api) {
      return
    }

    await configStore.api.toggleFavorite(userId)
    await fetchFavorites()
  }

  function isFavorite(userId: number | string): boolean {
    return favorites.value.some((user) => String(user.id) === String(userId))
  }

  return {
    searchResults,
    favorites,
    searching,
    searchError,
    search,
    fetchFavorites,
    toggleFavorite,
    isFavorite,
  }
})
