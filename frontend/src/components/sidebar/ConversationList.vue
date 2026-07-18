<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { storeToRefs } from 'pinia'
import type { ChatifyConversation } from '../../types'
import { useConfirmStore } from '../../stores/confirm'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { useConfigStore } from '../../stores/config'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { conversationListKey } from '../../utils/sortConversations'
import {
  buildConversationActionItems,
  type ConversationActionId,
} from '../../utils/conversationActionItems'
import ConversationListItem from './ConversationListItem.vue'
import GroupConversationListItem from './GroupConversationListItem.vue'
import ConversationListSkeleton from '../skeletons/ConversationListSkeleton.vue'
import EmptyState from '../states/EmptyState.vue'
import ContextMenu from '../ui/ContextMenu.vue'

const conversationsStore = useConversationsStore()
const contactsStore = useContactsStore()
const confirmStore = useConfirmStore()
const uiStore = useUiStore()
const configStore = useConfigStore()
const { isMobile } = useBreakpoints()

const {
  filteredItems,
  pinnedItems,
  recentItems,
  savedItems,
  loading,
  loadFailed,
  activeId,
} = storeToRefs(conversationsStore)

const draggablePinned = ref<ChatifyConversation[]>([])
const menuOpen = ref(false)
const menuX = ref(0)
const menuY = ref(0)
const menuConversation = ref<ChatifyConversation | null>(null)

watch(
  pinnedItems,
  (items) => {
    draggablePinned.value = [...items]
  },
  { deep: true, immediate: true },
)

const menuItems = computed(() => {
  if (!menuConversation.value) {
    return []
  }

  const otherUser = menuConversation.value.relationships.other_user

  return buildConversationActionItems({
    conversation: menuConversation.value,
    isFavorite: otherUser ? contactsStore.isFavorite(otherUser.id) : false,
    isBlocked: otherUser ? contactsStore.isBlocked(otherUser.id) : false,
    isMessagingBlocked: otherUser ? contactsStore.isMessagingBlocked(otherUser.id) : false,
  })
})

async function selectConversation(id: string) {
  await conversationsStore.select(id)
  if (isMobile.value) {
    uiStore.showThread()
  }
}

function openContextMenu(event: MouseEvent, conversation: ChatifyConversation) {
  event.preventDefault()
  menuConversation.value = conversation
  menuX.value = event.clientX
  menuY.value = event.clientY
  menuOpen.value = true
}

function closeContextMenu() {
  menuOpen.value = false
  menuConversation.value = null
}

async function onPinReorder() {
  if (draggablePinned.value.length === 0) {
    draggablePinned.value = [...pinnedItems.value]
    return
  }

  await conversationsStore.reorderPinned(draggablePinned.value.map((conversation) => conversation.id))
}

async function onMenuSelect(actionId: string) {
  const conversation = menuConversation.value
  if (!conversation) {
    return
  }

  const id = actionId as ConversationActionId
  const otherUser = conversation.relationships.other_user

  switch (id) {
    case 'pin':
    case 'unpin':
      await conversationsStore.togglePin(conversation.id, id === 'pin')
      break
    case 'markRead':
      await conversationsStore.markRead(conversation.id)
      break
    case 'contactInfo':
      if (otherUser) {
        uiStore.openModal('contactInfo', { user: otherUser })
      }
      break
    case 'groupInfo':
      uiStore.openModal('groupInfo', { conversationId: conversation.id })
      break
    case 'favorite':
    case 'unfavorite':
      if (otherUser) {
        await contactsStore.toggleFavorite(otherUser.id)
      }
      break
    case 'block':
      if (otherUser) {
        const confirmed = await confirmStore.confirm({
          title: `Block ${otherUser.attributes.name}?`,
          message: 'They will not be able to message you, and you will not see them in search.',
          confirmLabel: 'Block',
          variant: 'danger',
        })
        if (confirmed) {
          await contactsStore.blockUser(otherUser.id)
        }
      }
      break
    case 'unblock':
      if (otherUser) {
        await contactsStore.unblockUser(otherUser.id)
      }
      break
    case 'hideConversation': {
      const confirmed = await confirmStore.confirm({
        title: 'Delete conversation?',
        message: "Remove this chat from your inbox. The other person won't be affected.",
        confirmLabel: 'Delete conversation',
        variant: 'danger',
      })
      if (confirmed && configStore.api) {
        await configStore.api.hideConversation(conversation.id)
        conversationsStore.removeConversation(conversation.id)
        conversationsStore.clearActive()
      }
      break
    }
    case 'clearSavedMessages': {
      const confirmed = await confirmStore.confirm({
        title: 'Clear Saved Messages?',
        message: 'All messages in Saved Messages will be permanently deleted.',
        confirmLabel: 'Clear chat',
        variant: 'danger',
      })
      if (confirmed) {
        await conversationsStore.clearSavedMessages(conversation.id)
      }
      break
    }
    case 'leaveGroup': {
      const confirmed = await confirmStore.confirm({
        title: 'Leave this group?',
        message: "You won't receive new messages from this group.",
        confirmLabel: 'Leave group',
        variant: 'danger',
      })
      if (confirmed && configStore.api) {
        await configStore.api.leaveGroup(conversation.id)
        conversationsStore.removeConversation(conversation.id)
        conversationsStore.clearActive()
      }
      break
    }
    case 'deleteGroup': {
      const confirmed = await confirmStore.confirm({
        title: 'Delete group?',
        message: 'This permanently deletes the group for everyone.',
        confirmLabel: 'Delete group',
        variant: 'danger',
      })
      if (confirmed && configStore.api) {
        await configStore.api.deleteConversation(conversation.id)
        conversationsStore.removeConversation(conversation.id)
        conversationsStore.clearActive()
      }
      break
    }
  }
}
</script>

<template>
  <div
    class="chatify:flex-1 chatify:overflow-y-auto"
    @contextmenu.prevent
  >
    <ConversationListSkeleton v-if="loading && filteredItems.length === 0" />

    <EmptyState
      v-else-if="loadFailed"
      title="Unable to load conversations"
      description="We couldn't load your conversations right now. Please check your connection and try again."
    >
      <button
        type="button"
        class="chatify:mt-2 chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-2 chatify:text-sm chatify:text-white"
        @click="conversationsStore.fetchAll()"
      >
        Try again
      </button>
    </EmptyState>

    <EmptyState
      v-else-if="filteredItems.length === 0"
      title="No conversations yet"
      description="Start a new chat from contacts search or create a group."
    />

    <template v-else>
      <ul
        v-if="savedItems.length > 0"
        class="chatify:divide-y chatify:divide-chatify-border"
      >
        <li
          v-for="conversation in savedItems"
          :key="conversationListKey(conversation)"
          @contextmenu="openContextMenu($event, conversation)"
        >
          <ConversationListItem
            :conversation="conversation"
            :active="conversation.id === activeId"
            @select="selectConversation(conversation.id)"
          />
        </li>
      </ul>

      <VueDraggable
        v-if="pinnedItems.length > 0"
        v-model="draggablePinned"
        handle=".chatify-conversation-drag-handle"
        :animation="180"
        direction="vertical"
        class="chatify:divide-y chatify:divide-chatify-border"
        @end="onPinReorder"
      >
        <div
          v-for="conversation in draggablePinned"
          :key="conversationListKey(conversation)"
          @contextmenu="openContextMenu($event, conversation)"
        >
          <GroupConversationListItem
            v-if="conversation.attributes.conversation_type === 'group'"
            :conversation="conversation"
            :active="conversation.id === activeId"
            pinned
            draggable
            @select="selectConversation(conversation.id)"
          />
          <ConversationListItem
            v-else
            :conversation="conversation"
            :active="conversation.id === activeId"
            pinned
            draggable
            @select="selectConversation(conversation.id)"
          />
        </div>
      </VueDraggable>

      <ul class="chatify:divide-y chatify:divide-chatify-border">
        <li
          v-for="conversation in recentItems"
          :key="conversationListKey(conversation)"
          @contextmenu="openContextMenu($event, conversation)"
        >
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
    </template>

    <ContextMenu
      :open="menuOpen"
      :x="menuX"
      :y="menuY"
      :items="menuItems"
      @select="onMenuSelect"
      @close="closeContextMenu"
    />
  </div>
</template>
