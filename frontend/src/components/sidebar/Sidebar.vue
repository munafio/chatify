<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfigStore } from '../../stores/config'
import { useConnectionStore } from '../../stores/connection'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { sidebarSubtitleLabel } from '../../utils/connectionLabel'
import ConversationList from './ConversationList.vue'
import ConversationSearch from './ConversationSearch.vue'
import FavoriteContactsList from './FavoriteContactsList.vue'
import UserAvatar from '../ui/UserAvatar.vue'

const configStore = useConfigStore()
const connectionStore = useConnectionStore()
const uiStore = useUiStore()
const conversationsStore = useConversationsStore()
const { user, groupsEnabled } = storeToRefs(configStore)
const { sidebarTab } = storeToRefs(conversationsStore)
const { uiState } = storeToRefs(connectionStore)

const sidebarSubtitle = computed(() => sidebarSubtitleLabel(uiState.value))
</script>

<template>
  <div class="chatify:flex chatify:h-full chatify:min-h-0 chatify:flex-col">
    <header class="chatify-sidebar-divide chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-3 chatify:border-b chatify:bg-chatify-sidebar chatify:px-4 chatify:py-3">
      <UserAvatar v-if="user" size="sm" :alt="user.attributes.name" />
      <div class="chatify:min-w-0 chatify:flex-1">
        <p class="chatify:truncate chatify:text-sm chatify:font-semibold">{{ user?.attributes.name }}</p>
        <p
          class="chatify:text-xs chatify:text-chatify-muted"
          :class="{ 'chatify:animate-pulse': uiState !== 'online' }"
        >
          {{ sidebarSubtitle }}
        </p>
      </div>
      <button
        type="button"
        class="chatify:rounded-full chatify:p-2 chatify:hover:bg-black/10"
        aria-label="Settings"
        @click="uiStore.openModal('settings')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
      <button
        type="button"
        class="chatify:rounded-full chatify:bg-chatify-primary chatify-accent-gradient chatify:p-2 chatify:text-white"
        aria-label="New chat"
        @click="uiStore.openModal('newChat')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
      </button>
      <button
        v-if="groupsEnabled"
        type="button"
        class="chatify:rounded-full chatify:bg-chatify-primary chatify-accent-gradient chatify:p-2 chatify:text-white"
        aria-label="Create group"
        @click="uiStore.openModal('createGroup')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
      </button>
    </header>

    <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:overflow-hidden">
      <template v-if="sidebarTab === 'chats'">
        <ConversationSearch />
        <ConversationList />
      </template>

      <FavoriteContactsList v-else />
    </div>

    <nav
      class="chatify-bottom-bar chatify-sidebar-bottom-nav chatify:flex chatify:items-center chatify:justify-around chatify:border-t chatify:bg-chatify-sidebar chatify:px-2"
      aria-label="Sidebar navigation"
    >
      <button
        type="button"
        class="chatify-sidebar-nav-item"
        :class="{ 'chatify-sidebar-nav-item-active': sidebarTab === 'chats' }"
        @click="conversationsStore.setSidebarTab('chats')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <span>Chats</span>
      </button>

      <button
        type="button"
        class="chatify-sidebar-nav-item"
        :class="{ 'chatify-sidebar-nav-item-active': sidebarTab === 'favorites' }"
        @click="conversationsStore.setSidebarTab('favorites')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
        <span>Favorites</span>
      </button>
    </nav>
  </div>
</template>
