import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { ChatifyConversation } from '../types'
import { useConfigStore } from './config'

export const useConversationsStore = defineStore('conversations', () => {
  const items = ref<ChatifyConversation[]>([])
  const activeId = ref<string | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const searchQuery = ref('')
  const sidebarTab = ref<'chats' | 'favorites'>('chats')

  const configStore = useConfigStore()

  const activeConversation = computed(() =>
    items.value.find((item) => item.id === activeId.value) ?? null,
  )

  const filteredItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) {
      return items.value
    }

    return items.value.filter((conversation) => {
      const name =
        conversation.attributes.conversation_type === 'group'
          ? conversation.attributes.name ?? ''
          : conversation.relationships.other_user?.attributes.name ?? ''
      return name.toLowerCase().includes(q)
    })
  })

  function sortByRecent(list: ChatifyConversation[]): ChatifyConversation[] {
    return [...list].sort((a, b) => {
      const aTime = a.relationships.last_message?.attributes.created_at
        ?? a.attributes.updated_at
        ?? a.attributes.created_at
        ?? ''
      const bTime = b.relationships.last_message?.attributes.created_at
        ?? b.attributes.updated_at
        ?? b.attributes.created_at
        ?? ''
      return bTime.localeCompare(aTime)
    })
  }

  function upsert(conversation: ChatifyConversation) {
    const index = items.value.findIndex((item) => item.id === conversation.id)
    if (index >= 0) {
      items.value[index] = conversation
    } else {
      items.value.unshift(conversation)
    }
    items.value = sortByRecent(items.value)
  }

  function upsertInboxItem(conversation: ChatifyConversation) {
    upsert(conversation)
  }

  function bumpConversationToTop(conversationId: string) {
    const index = items.value.findIndex((item) => item.id === conversationId)
    if (index <= 0) {
      items.value = sortByRecent(items.value)
      return
    }

    const [conversation] = items.value.splice(index, 1)
    items.value.unshift(conversation)
    items.value = sortByRecent(items.value)
  }

  function updateLastMessage(conversationId: string, message: ChatifyConversation['relationships']['last_message']) {
    const conversation = items.value.find((item) => item.id === conversationId)
    if (conversation) {
      conversation.relationships.last_message = message
      conversation.attributes.updated_at = message?.attributes.created_at ?? conversation.attributes.updated_at
      bumpConversationToTop(conversationId)
    }
  }

  function updateUnreadCount(conversationId: string, count: number) {
    const conversation = items.value.find((item) => item.id === conversationId)
    if (conversation) {
      conversation.attributes.unread_count = count
    }
  }

  function updateParticipants(conversationId: string, participants: ChatifyConversation['relationships']['participants'], count: number) {
    const conversation = items.value.find((item) => item.id === conversationId)
    if (conversation) {
      conversation.relationships.participants = participants
      conversation.attributes.participant_count = count
    }
  }

  async function fetchAll() {
    if (!configStore.api) {
      return
    }

    loading.value = true
    error.value = null

    try {
      const { data } = await configStore.api.getConversations()
      items.value = sortByRecent(data.data)
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to load conversations'
    } finally {
      loading.value = false
    }
  }

  async function select(id: string) {
    activeId.value = id
    if (!configStore.api) {
      return
    }

    try {
      const { data } = await configStore.api.getConversation(id)
      upsert(data.data)
    } catch {
      // keep cached conversation if fetch fails
    }
  }

  async function startDirect(userId: number | string) {
    if (!configStore.api) {
      return null
    }

    const { data } = await configStore.api.createDirectConversation(userId)
    upsert(data.data)
    activeId.value = data.data.id
    sidebarTab.value = 'chats'
    return data.data
  }

  async function createGroup(name: string, userIds: Array<number | string>) {
    if (!configStore.api) {
      return null
    }

    const { data } = await configStore.api.createGroupConversation(name, userIds)
    upsert(data.data)
    activeId.value = data.data.id
    return data.data
  }

  async function markRead(conversationId: string) {
    if (!configStore.api) {
      return
    }

    await configStore.api.markConversationRead(conversationId)
    updateUnreadCount(conversationId, 0)
  }

  function handleInboxUpdate(conversation: ChatifyConversation, _currentUserId: number | string) {
    if (activeId.value === conversation.id) {
      conversation.attributes.unread_count = 0
    }

    upsertInboxItem(conversation)
  }

  function setSearchQuery(query: string) {
    searchQuery.value = query
  }

  function setSidebarTab(tab: 'chats' | 'favorites') {
    sidebarTab.value = tab
  }

  function clearActive() {
    activeId.value = null
  }

  return {
    items,
    activeId,
    loading,
    error,
    searchQuery,
    sidebarTab,
    activeConversation,
    filteredItems,
    upsert,
    upsertInboxItem,
    bumpConversationToTop,
    updateLastMessage,
    updateUnreadCount,
    updateParticipants,
    handleInboxUpdate,
    fetchAll,
    select,
    startDirect,
    createGroup,
    markRead,
    setSearchQuery,
    setSidebarTab,
    clearActive,
  }
})
