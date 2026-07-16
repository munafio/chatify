<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import EmptyState from '../states/EmptyState.vue'
import MessageComposer from './MessageComposer.vue'
import MessageList from './MessageList.vue'
import ThreadHeader from './ThreadHeader.vue'
import ThreadHeaderSkeleton from '../skeletons/ThreadHeaderSkeleton.vue'

const conversationsStore = useConversationsStore()
const uiStore = useUiStore()
const { isMobile } = useBreakpoints()
const { activeConversation, activeId } = storeToRefs(conversationsStore)

function goBack() {
  uiStore.hideThread()
  conversationsStore.clearActive()
}
</script>

<template>
  <EmptyState
    v-if="!activeId"
    title="Select a conversation"
    description="Choose a chat from the sidebar or start a new one."
    class="chatify:flex-1"
  />

  <template v-else>
    <ThreadHeaderSkeleton v-if="!activeConversation" />
    <ThreadHeader
      v-else
      :conversation="activeConversation"
      :show-back="isMobile"
      @back="goBack"
    />

    <MessageList />

    <MessageComposer v-if="activeConversation" :conversation-id="activeConversation.id" />
  </template>
</template>
