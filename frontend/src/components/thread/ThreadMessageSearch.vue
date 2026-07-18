<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyMessage } from '../../types'
import { useDebouncedWatch } from '../../composables/useDebouncedFn'
import { useConfigStore } from '../../stores/config'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { groupMessagesByDate } from '../../utils/groupMessageClusters'
import { highlightQuery } from '../../utils/highlightQuery'
import { isParticipantRecord, participantUser } from '../../utils/group'
import EmptyState from '../states/EmptyState.vue'
import MessageDateSeparator from './MessageDateSeparator.vue'
import MessageDeliveryStatus from './MessageDeliveryStatus.vue'

const uiStore = useUiStore()
const configStore = useConfigStore()
const conversationsStore = useConversationsStore()
const { messageSearchOpen } = storeToRefs(uiStore)
const { activeConversation } = storeToRefs(conversationsStore)

const query = ref('')
const results = ref<ChatifyMessage[]>([])
const loading = ref(false)
const searchInput = ref<HTMLInputElement | null>(null)

const isGroup = computed(() => activeConversation.value?.attributes.conversation_type === 'group')

const participantMap = computed(() => {
  const names = new Map<string, string>()
  const avatars = new Map<string, string>()

  activeConversation.value?.relationships.participants.forEach((item) => {
    const user = isParticipantRecord(item) ? participantUser(item) : item
    if (user) {
      names.set(String(user.id), user.attributes.name)
      avatars.set(String(user.id), user.attributes.avatar)
    }
  })

  activeConversation.value?.relationships.participants_preview?.forEach((item) => {
    const user = participantUser(item)
    if (user) {
      names.set(String(user.id), user.attributes.name)
      avatars.set(String(user.id), user.attributes.avatar)
    }
  })

  return { names, avatars }
})

const groupedResults = computed(() => groupMessagesByDate(results.value))

const showEmptyPrompt = computed(
  () => !loading.value && query.value.trim().length < 2 && results.value.length === 0,
)

const showNoResults = computed(
  () => !loading.value && query.value.trim().length >= 2 && results.value.length === 0,
)

useDebouncedWatch(query, async (value) => {
  const conversationId = conversationsStore.activeId
  if (!messageSearchOpen.value || !conversationId || !configStore.api || value.trim().length < 2) {
    results.value = []
    return
  }

  loading.value = true
  try {
    const { data } = await configStore.api.searchMessages(conversationId, value.trim(), { per_page: 20 })
    results.value = data.data
  } finally {
    loading.value = false
  }
})

watch(messageSearchOpen, async (open) => {
  if (open) {
    await nextTick()
    searchInput.value?.focus()
    return
  }

  query.value = ''
  results.value = []
})

function clearQuery() {
  query.value = ''
  results.value = []
  searchInput.value?.focus()
}

function senderName(message: ChatifyMessage): string {
  const senderId = String(message.relationships.sender.data.id)
  if (String(configStore.user?.id) === senderId) {
    return 'You'
  }
  return participantMap.value.names.get(senderId) ?? 'Member'
}

function senderAvatar(message: ChatifyMessage): string | undefined {
  return participantMap.value.avatars.get(String(message.relationships.sender.data.id))
}

function isOwnMessage(message: ChatifyMessage): boolean {
  return String(message.relationships.sender.data.id) === String(configStore.user?.id)
}

function messagePreview(message: ChatifyMessage): string {
  return message.attributes.body || 'Attachment'
}

async function jumpTo(message: ChatifyMessage) {
  uiStore.closeMessageSearch()
  await nextTick()
  document.querySelector(`[data-message-id="${message.id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}
</script>

<template>
  <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:bg-chatify-panel">
    <header class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-2 chatify:border-b chatify:border-chatify-border chatify:px-3 chatify:py-3">
      <button
        type="button"
        class="chatify:rounded-full chatify:p-1 chatify:text-chatify-text chatify:transition chatify:hover:bg-chatify-sidebar"
        aria-label="Close search"
        @click="uiStore.closeMessageSearch()"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <h2 class="chatify:flex-1 chatify:text-sm chatify:font-semibold">Search messages</h2>
    </header>

    <div class="chatify:shrink-0 chatify:border-b chatify:border-chatify-border chatify:px-3 chatify:py-2">
      <div class="chatify:flex chatify:items-center chatify:gap-2">
        <button
          type="button"
          class="chatify:rounded-full chatify:p-2 chatify:text-chatify-muted chatify:opacity-60"
          aria-label="Search by date"
          disabled
          title="Coming soon"
        >
          <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </button>

        <div class="chatify:relative chatify:min-w-0 chatify:flex-1">
          <svg
            class="chatify:pointer-events-none chatify:absolute chatify:top-1/2 chatify:left-3 chatify:h-4 chatify:w-4 chatify:-translate-y-1/2 chatify:text-chatify-muted"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            ref="searchInput"
            v-model="query"
            type="search"
            placeholder="Search"
            class="chatify-sidebar-input chatify:w-full chatify:rounded-lg chatify:py-2 chatify:pr-9 chatify:pl-9 chatify:text-sm"
          />
          <button
            v-if="query"
            type="button"
            class="chatify:absolute chatify:top-1/2 chatify:right-2 chatify:-translate-y-1/2 chatify:rounded-full chatify:p-1 chatify:text-chatify-muted chatify:hover:bg-chatify-sidebar"
            aria-label="Clear search"
            @click="clearQuery"
          >
            <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto chatify:px-3 chatify:py-2">
      <EmptyState
        v-if="showEmptyPrompt"
        title="Search for messages in this chat"
        description="Enter at least 2 characters to search."
      />

      <EmptyState
        v-else-if="showNoResults"
        title="No messages found"
        description="Try a different search term."
      />

      <ul v-else class="chatify:space-y-1">
        <template v-for="item in groupedResults" :key="item.key">
          <li v-if="item.kind === 'date'">
            <MessageDateSeparator :label="item.label" />
          </li>
          <li
            v-else-if="item.kind === 'message'"
            class="chatify-list-item chatify:cursor-pointer chatify:rounded-lg chatify:px-2 chatify:py-2.5"
            @click="jumpTo(item.message)"
          >
            <div class="chatify:flex chatify:items-start chatify:gap-2">
              <img
                v-if="isGroup && senderAvatar(item.message)"
                :src="senderAvatar(item.message)"
                :alt="senderName(item.message)"
                class="chatify:mt-0.5 chatify:h-7 chatify:w-7 chatify:rounded-full chatify:object-cover"
              />
              <div class="chatify:min-w-0 chatify:flex-1">
                <p class="chatify:truncate chatify:text-sm">
                  <span v-if="isGroup && !isOwnMessage(item.message)" class="chatify:text-chatify-muted">
                    {{ senderName(item.message) }}:
                  </span>
                  <span v-html="highlightQuery(messagePreview(item.message), query)" />
                </p>
              </div>
              <MessageDeliveryStatus
                v-if="isOwnMessage(item.message)"
                :read="item.message.attributes.read"
                :status="item.message.attributes.local_status ?? null"
                :is-own="true"
                class="chatify:mt-1 chatify:shrink-0"
              />
            </div>
          </li>
        </template>
      </ul>
    </div>
  </div>
</template>
