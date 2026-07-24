<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { ChatifyConversation, ChatifyParticipant, ChatifyUser } from '../../types'
import { useDebouncedWatch } from '../../composables/useDebouncedFn'
import { useInfiniteScroll } from '../../composables/useInfiniteScroll'
import { useConfigStore } from '../../stores/config'
import { useContactsStore } from '../../stores/contacts'
import { memberRoleBadgeClass, memberRoleLabel, participantUser } from '../../utils/group'
import { displayUserAvatar, displayUserName } from '../../utils/userDisplay'
import { mergeById } from '../../utils/mergeById'
import EmptyState from '../states/EmptyState.vue'
import GroupMembersSkeleton from '../skeletons/GroupMembersSkeleton.vue'
import DropdownMenu, { type DropdownMenuItem } from '../ui/DropdownMenu.vue'
import { useChatifyDirection } from '../../composables/useChatifyDirection'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  conversation: ChatifyConversation
  mode: 'browse' | 'add'
  canAddMembers: boolean
  canRemoveMembers: boolean
  canManageAdmins: boolean
  canTransferOwnership: boolean
  isOwner: boolean
  memberIds: Set<string>
  addMemberHandler?: (userId: number | string) => Promise<void>
}>()

const emit = defineEmits<{
  memberAction: [participant: ChatifyParticipant]
  transfer: [participant: ChatifyParticipant]
  remove: [participant: ChatifyParticipant]
  addMember: []
}>()

const configStore = useConfigStore()
const contactsStore = useContactsStore()
const { t } = useChatifyI18n()
const { isRtl } = useChatifyDirection()

const search = ref('')
const searchInput = ref<HTMLInputElement | null>(null)
const listScroll = ref<HTMLElement | null>(null)
const members = ref<ChatifyParticipant[]>([])
const page = ref(1)
const lastPage = ref(1)
const loadingMembers = ref(false)
const initialLoaded = ref(false)
const addingIds = ref<Set<string>>(new Set())
const addedFlashIds = ref<Set<string>>(new Set())
const flashTimers = new Map<string, number>()

async function loadMembers(reset = false) {
  if (!configStore.api || props.mode !== 'browse' || loadingMembers.value) {
    return
  }

  if (reset) {
    page.value = 1
    lastPage.value = 1
    members.value = []
    initialLoaded.value = false
  }

  if (page.value > lastPage.value && !reset) {
    return
  }

  loadingMembers.value = true
  try {
    const { data } = await configStore.api.getMembers(props.conversation.id, {
      page: page.value,
      per_page: 20,
      search: search.value.trim() || undefined,
    })

    members.value = reset ? data.data : mergeById(members.value, data.data)
    lastPage.value = data.meta?.last_page ?? 1
    page.value += 1
    initialLoaded.value = true
  } finally {
    loadingMembers.value = false
  }
}

useDebouncedWatch(search, (query) => {
  if (props.mode === 'add') {
    void contactsStore.search(query)
    return
  }

  if (!initialLoaded.value && !query) {
    return
  }

  void nextTick(resetListScroll)
  void loadMembers(true)
})

const { sentinel } = useInfiniteScroll(
  async () => {
    if (props.mode === 'browse' && initialLoaded.value) {
      await loadMembers(false)
    }
  },
  { root: listScroll },
)

function resetListScroll() {
  listScroll.value?.scrollTo({ top: 0 })
}

function focusSearchInput() {
  void nextTick(() => {
    searchInput.value?.focus()
  })
}

watch(
  () => [String(props.conversation.id), props.mode] as const,
  ([conversationId, mode], previous) => {
    const previousId = previous?.[0]
    const previousMode = previous?.[1]

    if (mode === 'browse') {
      if (previous === undefined || conversationId !== previousId || mode !== previousMode) {
        search.value = ''
        contactsStore.searchResults = []
        initialLoaded.value = false
        void nextTick(resetListScroll)
        void loadMembers(true)
      }
      return
    }

    if (previous === undefined || mode !== previousMode) {
      search.value = ''
      void nextTick(resetListScroll)
    } else if (conversationId !== previousId) {
      search.value = ''
      void nextTick(resetListScroll)
    }
  },
  { immediate: true },
)

watch(
  () => props.mode,
  (mode, previousMode) => {
    if (previousMode !== mode) {
      focusSearchInput()
    }
  },
)

onMounted(() => {
  focusSearchInput()
})

onBeforeUnmount(() => {
  flashTimers.forEach((timer) => window.clearTimeout(timer))
  flashTimers.clear()
})

function isAlreadyMember(userId: number | string) {
  return props.memberIds.has(String(userId))
}

function isAdding(userId: number | string) {
  return addingIds.value.has(String(userId))
}

function isAddedFlash(userId: number | string) {
  return addedFlashIds.value.has(String(userId))
}

function showAddedFlash(userId: number | string) {
  const id = String(userId)
  const next = new Set(addedFlashIds.value)
  next.add(id)
  addedFlashIds.value = next

  const existingTimer = flashTimers.get(id)
  if (existingTimer) {
    window.clearTimeout(existingTimer)
  }

  flashTimers.set(
    id,
    window.setTimeout(() => {
      const updated = new Set(addedFlashIds.value)
      updated.delete(id)
      addedFlashIds.value = updated
      flashTimers.delete(id)
    }, 1800),
  )
}

async function onAdd(user: ChatifyUser) {
  const id = String(user.id)
  const scrollTop = listScroll.value?.scrollTop ?? 0

  if (isAlreadyMember(user.id) || isAdding(user.id) || !props.addMemberHandler) {
    return
  }

  const nextAdding = new Set(addingIds.value)
  nextAdding.add(id)
  addingIds.value = nextAdding

  try {
    await props.addMemberHandler(user.id)
    showAddedFlash(user.id)
  } finally {
    const updatedAdding = new Set(addingIds.value)
    updatedAdding.delete(id)
    addingIds.value = updatedAdding
    await nextTick()
    if (listScroll.value) {
      listScroll.value.scrollTop = scrollTop
    }
  }
}

function memberDisplayName(participant: ChatifyParticipant): string {
  if (participant.attributes.is_you) {
    return t('ui.user.you')
  }

  return displayUserName(participantUser(participant))
}

function memberAvatar(participant: ChatifyParticipant): string {
  return displayUserAvatar(participantUser(participant), configStore.defaultAvatarUrl)
}

function canShowMemberMenu(participant: ChatifyParticipant): boolean {
  if (participant.attributes.is_you || participant.attributes.role === 'owner') {
    return false
  }

  return props.canManageAdmins || props.canRemoveMembers || props.canTransferOwnership
}

function memberMenuItems(_participant: ChatifyParticipant): DropdownMenuItem[] {
  const items: DropdownMenuItem[] = []

  if (props.canManageAdmins) {
    items.push({ id: 'role', label: t('ui.group.members.change_role') })
  }
  if (props.canTransferOwnership) {
    items.push({ id: 'transfer', label: t('ui.group.members.transfer_ownership') })
  }
  if (props.canRemoveMembers) {
    items.push({ id: 'remove', label: t('ui.group.members.remove_member'), danger: true })
  }

  return items
}

function onMemberMenuSelect(participant: ChatifyParticipant, id: string) {
  if (id === 'role') {
    emit('memberAction', participant)
    return
  }

  if (id === 'transfer') {
    emit('transfer', participant)
    return
  }

  if (id === 'remove') {
    emit('remove', participant)
  }
}
</script>

<template>
  <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col">
    <div class="chatify:mb-3">
      <input
        ref="searchInput"
        v-model="search"
        type="search"
        :placeholder="mode === 'add' ? t('ui.group.members.search_contacts') : t('ui.group.members.search_members')"
        class="chatify-sidebar-input chatify:w-full chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:text-sm chatify:text-chatify-text"
      />
    </div>

    <div ref="listScroll" class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto">
      <GroupMembersSkeleton v-if="mode === 'browse' && loadingMembers && members.length === 0" />

      <template v-else-if="mode === 'browse'">
        <button
          v-if="canAddMembers"
          type="button"
          class="chatify-list-item chatify:mb-1 chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:rounded-lg chatify:px-2 chatify:py-3 chatify:text-start"
          @click="emit('addMember')"
        >
          <span class="chatify:flex chatify:h-10 chatify:w-10 chatify:items-center chatify:justify-center chatify:rounded-full chatify:bg-chatify-primary chatify:text-chatify-text">
            <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
          </span>
          <span class="chatify:text-sm chatify:font-medium">{{ $t('ui.group.members.add_member') }}</span>
        </button>

        <EmptyState
          v-if="initialLoaded && members.length === 0"
          :title="t('ui.group.members.no_members_title')"
          :description="t('ui.group.members.no_members_description')"
        />

        <ul v-else>
          <li
            v-for="participant in members"
            :key="String(participant.id)"
            class="chatify-list-item chatify:flex chatify:items-center chatify:gap-3 chatify:rounded-lg chatify:px-2 chatify:py-3"
          >
            <img
              :src="memberAvatar(participant)"
              :alt="memberDisplayName(participant)"
              class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover"
            />
            <div class="chatify:min-w-0 chatify:flex-1">
              <div class="chatify:flex chatify:items-center chatify:gap-2">
                <p class="chatify:truncate chatify:text-sm chatify:font-medium">
                  {{ memberDisplayName(participant) }}
                </p>
                <span
                  v-if="memberRoleLabel(participant.attributes.role, participant.attributes.is_full_admin)"
                  :class="memberRoleBadgeClass(participant.attributes.role)"
                >
                  {{ memberRoleLabel(participant.attributes.role, participant.attributes.is_full_admin) }}
                </span>
              </div>
            </div>
            <DropdownMenu
              v-if="canShowMemberMenu(participant)"
              :items="memberMenuItems(participant)"
              :align="isRtl ? 'start' : 'end'"
              @select="onMemberMenuSelect(participant, $event)"
            />
          </li>
        </ul>
      </template>

      <template v-else>
        <EmptyState
          v-if="!search.trim()"
          :title="t('ui.group.members.search_to_add_title')"
          :description="t('ui.group.members.search_to_add_description')"
        />

        <EmptyState
          v-else-if="contactsStore.searchResults.length === 0 && !contactsStore.searching"
          :title="t('ui.group.members.no_contacts_title')"
          :description="t('ui.group.members.no_contacts_description')"
        />

        <ul v-else>
          <li
            v-for="user in contactsStore.searchResults"
            :key="String(user.id)"
            class="chatify-list-item chatify:flex chatify:items-center chatify:justify-between chatify:gap-3 chatify:rounded-lg chatify:px-2 chatify:py-3"
          >
            <div class="chatify:flex chatify:min-w-0 chatify:items-center chatify:gap-3">
              <img :src="displayUserAvatar(user, configStore.defaultAvatarUrl)" :alt="displayUserName(user)" class="chatify:h-10 chatify:w-10 chatify:rounded-full chatify:object-cover" />
              <div>
                <p class="chatify:text-sm chatify:font-medium">{{ displayUserName(user) }}</p>
                <p v-if="isAlreadyMember(user.id) && !isAddedFlash(user.id)" class="chatify:text-xs chatify:text-chatify-muted">
                  {{ $t('ui.group.members.already_member') }}
                </p>
              </div>
            </div>

            <span
              v-if="isAddedFlash(user.id)"
              class="chatify-member-added-check"
              :aria-label="t('ui.group.members.added')"
            >
              <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <span
              v-else-if="isAdding(user.id)"
              class="chatify:px-3 chatify:py-1 chatify:text-xs chatify:text-chatify-muted"
            >
              {{ $t('ui.group.members.adding') }}
            </span>
            <button
              v-else-if="!isAlreadyMember(user.id)"
              type="button"
              class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-3 chatify:py-1 chatify:text-xs chatify:text-white"
              @click="onAdd(user)"
            >
              {{ $t('ui.group.members.add') }}
            </button>
          </li>
        </ul>
      </template>

      <div ref="sentinel" class="chatify:h-4" />
    </div>
  </div>
</template>
