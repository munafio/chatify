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
import { isSavedConversation } from '../../utils/format'
import { displayUserAvatar, displayUserName } from '../../utils/userDisplay'
import { jumpToMessage } from '../../utils/jumpToMessage'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import EmptyState from '../states/EmptyState.vue'
import SearchField from '../ui/SearchField.vue'
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
const searchInput = ref<InstanceType<typeof SearchField> | null>(null)
const { t } = useChatifyI18n()

const isGroup = computed(() => activeConversation.value?.attributes.conversation_type === 'group')

const participantMap = computed(() => {
  const names = new Map<string, string>()
  const avatars = new Map<string, string>()
  const conversation = activeConversation.value

  if (configStore.user) {
    names.set(String(configStore.user.id), configStore.user.attributes.name)
    avatars.set(String(configStore.user.id), configStore.user.attributes.avatar)
  }

  conversation?.relationships.participants.forEach((item) => {
    const user = isParticipantRecord(item) ? participantUser(item) : item
    if (user) {
      names.set(String(user.id), displayUserName(user))
      avatars.set(String(user.id), displayUserAvatar(user, configStore.defaultAvatarUrl))
    }
  })

  conversation?.relationships.participants_preview?.forEach((item) => {
    const user = participantUser(item)
    if (user) {
      names.set(String(user.id), displayUserName(user))
      avatars.set(String(user.id), displayUserAvatar(user, configStore.defaultAvatarUrl))
    }
  })

  const otherUser = conversation?.relationships.other_user
  if (otherUser) {
    names.set(String(otherUser.id), displayUserName(otherUser))
    avatars.set(String(otherUser.id), displayUserAvatar(otherUser, configStore.defaultAvatarUrl))
  }

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

function senderName(message: ChatifyMessage): string {
  const senderId = String(message.relationships.sender.data.id)
  if (String(configStore.user?.id) === senderId) {
    return t('ui.user.you')
  }
  return participantMap.value.names.get(senderId) ?? t('system_messages.member')
}

function senderAvatar(message: ChatifyMessage): string | undefined {
  return participantMap.value.avatars.get(String(message.relationships.sender.data.id))
}

function isOwnMessage(message: ChatifyMessage): boolean {
  return String(message.relationships.sender.data.id) === String(configStore.user?.id)
}

function messagePreview(message: ChatifyMessage): string {
  return message.attributes.body || t('ui.thread.search.preview_attachment')
}

function showSenderLabel(message: ChatifyMessage): boolean {
  if (isOwnMessage(message)) {
    return false
  }

  if (isGroup.value) {
    return true
  }

  const conversation = activeConversation.value
  if (!conversation || isSavedConversation(conversation)) {
    return false
  }

  return conversation.attributes.conversation_type === 'direct'
}

async function jumpTo(message: ChatifyMessage) {
  uiStore.closeMessageSearch()
  await nextTick()
  jumpToMessage(message.id)
}
</script>

<template>
  <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:bg-chatify-panel">
    <header class="chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-2 chatify:border-b chatify:border-chatify-border chatify:px-3 chatify:py-3">
      <button
        type="button"
        class="chatify:rounded-full chatify:p-1 chatify:text-chatify-text chatify:transition chatify:hover:bg-chatify-sidebar"
        :aria-label="$t('ui.thread.search.close')"
        @click="uiStore.closeMessageSearch()"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
      <h2 class="chatify:flex-1 chatify:text-sm chatify:font-semibold">{{ $t('ui.thread.search.title') }}</h2>
    </header>

    <div class="chatify:shrink-0 chatify:border-b chatify:border-chatify-border chatify:px-3 chatify:py-2">
      <SearchField
        ref="searchInput"
        v-model="query"
        :placeholder="t('ui.thread.search.placeholder')"
        :clear-label="t('ui.thread.search.clear')"
        input-class="chatify-sidebar-input chatify:rounded-lg chatify:py-2 chatify:text-sm chatify:text-chatify-text"
      />
    </div>

    <div class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto chatify:px-3 chatify:py-2">
      <EmptyState
        v-if="showEmptyPrompt"
        :title="$t('ui.thread.search.prompt_title')"
        :description="$t('ui.thread.search.prompt_description')"
      />

      <EmptyState
        v-else-if="showNoResults"
        :title="$t('ui.thread.search.no_results_title')"
        :description="$t('ui.thread.search.no_results_description')"
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
                  <span v-if="showSenderLabel(item.message)" class="chatify:text-chatify-muted">
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
