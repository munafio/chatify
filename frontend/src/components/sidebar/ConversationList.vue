<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { useBreakpoints } from '../../composables/useBreakpoints'
import ConversationListItem from './ConversationListItem.vue'
import GroupConversationListItem from './GroupConversationListItem.vue'
import ConversationListSkeleton from '../skeletons/ConversationListSkeleton.vue'
import EmptyState from '../states/EmptyState.vue'
import ErrorState from '../states/ErrorState.vue'

const conversationsStore = useConversationsStore()
const uiStore = useUiStore()
const { isMobile } = useBreakpoints()
const { filteredItems, loading, error, activeId } = storeToRefs(conversationsStore)

async function selectConversation(id: string) {
  await conversationsStore.select(id)
  if (isMobile.value) {
    uiStore.showThread()
  }
}
</script>

<template>
  <div class="chatify:flex-1 chatify:overflow-y-auto">
    <ConversationListSkeleton v-if="loading && filteredItems.length === 0" />

    <ErrorState
      v-else-if="error"
      :message="error"
      @retry="conversationsStore.fetchAll()"
    />

    <EmptyState
      v-else-if="filteredItems.length === 0"
      title="No conversations yet"
      description="Start a new chat from contacts search or create a group."
    />

    <ul v-else class="chatify:divide-y chatify:divide-chatify-border">
      <li v-for="conversation in filteredItems" :key="conversation.id">
        <GroupConversationListItem
          v-if="conversation.attributes.conversation_type === 'group'"
          :conversation="conversation"
          :active="conversation.id === activeId"
          @select="selectConversation(conversation.id)"
        />
        <ConversationListItem
          v-else
          :conversation="conversation"
          :active="conversation.id === activeId"
          @select="selectConversation(conversation.id)"
        />
      </li>
    </ul>
  </div>
</template>
