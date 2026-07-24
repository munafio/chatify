<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import type { ChatifyUser } from '../../types'
import { useConfirmStore } from '../../stores/confirm'
import { useContactsStore } from '../../stores/contacts'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import EmptyState from '../states/EmptyState.vue'

const contactsStore = useContactsStore()
const confirmStore = useConfirmStore()
const { t } = useChatifyI18n()
const { blockedUsers, blockedLoadFailed, blockedLoading } = storeToRefs(contactsStore)

const search = ref('')

const filteredBlockedUsers = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) {
    return blockedUsers.value
  }

  return blockedUsers.value.filter((user) => {
    const name = user.attributes.name.toLowerCase()
    const email = user.attributes.email?.toLowerCase() ?? ''
    return name.includes(q) || email.includes(q)
  })
})

onMounted(() => {
  void contactsStore.fetchBlocked()
})

async function unblockUser(user: ChatifyUser) {
  const confirmed = await confirmStore.confirm({
    title: t('ui.confirm.unblock.title', { name: user.attributes.name }),
    message: t('ui.confirm.unblock.message'),
    confirmLabel: t('ui.confirm.unblock.confirm'),
  })

  if (confirmed) {
    await contactsStore.unblockUser(user.id)
  }
}
</script>

<template>
  <div class="chatify:flex chatify:min-h-0 chatify:flex-col">
    <p class="chatify:mb-3 chatify:px-1 chatify:text-xs chatify:text-chatify-muted">
      {{ $t('ui.settings.blocked.intro') }}
    </p>

    <div class="chatify:mb-3 chatify:rounded-xl chatify:border chatify:border-chatify-border chatify:bg-chatify-panel">
      <input
        v-model="search"
        type="search"
        :placeholder="t('ui.settings.blocked.search_placeholder')"
        class="chatify:w-full chatify:rounded-xl chatify:bg-transparent chatify:px-4 chatify:py-3 chatify:text-sm chatify:text-chatify-text"
      />
    </div>

    <EmptyState
      v-if="blockedLoadFailed"
      :title="t('ui.settings.blocked.load_failed_title')"
      :description="t('ui.settings.blocked.load_failed_description')"
    >
      <button
        type="button"
        class="chatify:mt-2 chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-2 chatify:text-sm chatify:text-white"
        @click="contactsStore.fetchBlocked()"
      >
        {{ $t('ui.common.try_again') }}
      </button>
    </EmptyState>

    <div
      v-else-if="blockedLoading && blockedUsers.length === 0"
      class="chatify:py-8 chatify:text-center chatify:text-sm chatify:text-chatify-muted"
    >
      {{ $t('ui.settings.blocked.loading') }}
    </div>

    <div
      v-else-if="blockedUsers.length === 0"
      class="chatify:flex chatify:flex-col chatify:items-center chatify:gap-3 chatify:rounded-xl chatify:border chatify:border-chatify-border chatify:bg-chatify-panel chatify:px-6 chatify:py-10 chatify:text-center"
    >
      <div class="chatify:flex chatify:h-14 chatify:w-14 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-sidebar chatify:text-chatify-muted">
        <svg class="chatify:h-7 chatify:w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>
      <div>
        <h3 class="chatify:text-sm chatify:font-semibold chatify:text-chatify-text">{{ $t('ui.settings.blocked.empty_title') }}</h3>
        <p class="chatify:mt-1 chatify:text-xs chatify:text-chatify-muted">
          {{ $t('ui.settings.blocked.empty_description') }}
        </p>
      </div>
    </div>

    <p
      v-else-if="filteredBlockedUsers.length === 0"
      class="chatify:py-6 chatify:text-center chatify:text-sm chatify:text-chatify-muted"
    >
      {{ $t('ui.settings.blocked.no_match') }}
    </p>

    <div v-else class="chatify:overflow-hidden chatify:rounded-xl chatify:border chatify:border-chatify-border chatify:bg-chatify-panel">
      <div
        v-for="(user, index) in filteredBlockedUsers"
        :key="user.id"
        class="chatify:flex chatify:items-center chatify:gap-3 chatify:px-4 chatify:py-3"
        :class="index > 0 ? 'chatify:border-t chatify:border-chatify-border' : ''"
      >
        <div class="chatify:h-10 chatify:w-10 chatify:shrink-0">
          <img
            :src="user.attributes.avatar"
            :alt="user.attributes.name"
            class="chatify:h-full chatify:w-full chatify:rounded-full chatify:object-cover"
          />
        </div>
        <div class="chatify:min-w-0 chatify:flex-1">
          <p class="chatify:truncate chatify:text-sm chatify:font-medium chatify:text-chatify-text">
            {{ user.attributes.name }}
          </p>
        </div>
        <button
          type="button"
          class="chatify:shrink-0 chatify:rounded-lg chatify:border chatify:border-chatify-border chatify:px-3 chatify:py-1.5 chatify:text-xs chatify:font-medium chatify:text-chatify-primary chatify:hover:bg-chatify-sidebar"
          @click="unblockUser(user)"
        >
          {{ $t('ui.settings.blocked.unblock') }}
        </button>
      </div>
    </div>
  </div>
</template>
