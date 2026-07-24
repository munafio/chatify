<script setup lang="ts">
import { computed, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useContactsStore } from '../../stores/contacts'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { useConfigStore } from '../../stores/config'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import { displayUserAvatar, displayUserName } from '../../utils/userDisplay'
import EmptyState from '../states/EmptyState.vue'

const contactsStore = useContactsStore()
const conversationsStore = useConversationsStore()
const uiStore = useUiStore()
const configStore = useConfigStore()
const { t } = useChatifyI18n()
const { favorites, favoritesLoadFailed } = storeToRefs(contactsStore)

const search = ref('')

const visibleFavorites = computed(() =>
  favorites.value.filter((user) => !contactsStore.isBlockedByMe(user.id)),
)

const filteredFavorites = computed(() => {
  const q = search.value.trim().toLowerCase()
  const source = visibleFavorites.value
  if (!q) {
    return source
  }

  return source.filter((user) => {
    const name = displayUserName(user).toLowerCase()
    const email = user.attributes.email?.toLowerCase() ?? ''
    return name.includes(q) || email.includes(q)
  })
})

async function openFavorite(userId: number | string) {
  await conversationsStore.startDirect(userId)
  uiStore.showThread()
}
</script>

<template>
  <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col chatify:overflow-hidden">
    <div class="chatify-sidebar-divide chatify:shrink-0 chatify:border-b chatify:px-3 chatify:py-2">
      <input
        v-model="search"
        type="search"
        :placeholder="t('ui.sidebar.search_favorites')"
        class="chatify-sidebar-input chatify:w-full chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:text-sm chatify:text-chatify-text"
      />
    </div>

    <EmptyState
      v-if="favoritesLoadFailed"
      class="chatify:flex-1"
      :title="t('ui.sidebar.errors.load_favorites_title')"
      :description="t('ui.sidebar.errors.load_favorites_description')"
    >
      <button
        type="button"
        class="chatify:mt-2 chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-2 chatify:text-sm chatify:text-white"
        @click="contactsStore.fetchFavorites()"
      >
        {{ $t('ui.sidebar.try_again') }}
      </button>
    </EmptyState>

    <div
      v-else-if="visibleFavorites.length === 0"
      class="chatify:flex chatify:flex-1 chatify:flex-col chatify:items-center chatify:justify-center chatify:gap-3 chatify:p-8 chatify:text-center"
    >
      <div class="chatify:flex chatify:h-16 chatify:w-16 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-amber-100 chatify:text-amber-500">
        <svg class="chatify:h-8 chatify:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
      </div>
      <h3 class="chatify:text-base chatify:font-semibold">{{ $t('ui.sidebar.empty.no_favorites_title') }}</h3>
      <p class="chatify:max-w-xs chatify:text-sm chatify:text-chatify-muted">
        {{ $t('ui.sidebar.empty.no_favorites_description') }}
      </p>
    </div>

    <p
      v-else-if="filteredFavorites.length === 0"
      class="chatify:p-6 chatify:text-center chatify:text-sm chatify:text-chatify-muted"
    >
      {{ $t('ui.sidebar.empty.no_favorites_match') }}
    </p>

    <div v-else class="chatify:flex-1 chatify:overflow-y-auto">
      <button
        v-for="user in filteredFavorites"
        :key="user.id"
        type="button"
        class="chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:px-4 chatify:py-3 chatify:text-start"
        @click="openFavorite(user.id)"
      >
        <div class="chatify:h-10 chatify:w-10 chatify:shrink-0">
          <img
            :src="displayUserAvatar(user, configStore.defaultAvatarUrl)"
            :alt="displayUserName(user)"
            class="chatify:h-full chatify:w-full chatify:rounded-full chatify:object-cover"
          />
        </div>
        <div class="chatify:min-w-0 chatify:flex-1">
          <p class="chatify:truncate chatify:text-sm chatify:font-medium chatify:text-chatify-text">{{ displayUserName(user) }}</p>
        </div>
      </button>
    </div>
  </div>
</template>
