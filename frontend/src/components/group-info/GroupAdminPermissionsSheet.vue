<script setup lang="ts">
import { ref, watch } from 'vue'
import type { GroupPermissionKey } from '../../types'
import BaseModal from '../modals/BaseModal.vue'
import SettingsGroup from '../settings/SettingsGroup.vue'

type RoleMode = 'full' | 'limited' | 'moderator' | 'member'

const props = defineProps<{
  open: boolean
  memberName: string
  canPromoteFullAdmin: boolean
}>()

const emit = defineEmits<{
  close: []
  save: [payload: { role: 'admin' | 'moderator' | 'member'; permissions: Partial<Record<GroupPermissionKey, boolean>> | null }]
}>()

const mode = ref<RoleMode>('member')
const permissions = ref<Partial<Record<GroupPermissionKey, boolean>>>({
  edit_info: false,
  add_members: false,
  remove_members: false,
  manage_admins: false,
})

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      mode.value = 'member'
      permissions.value = {
        edit_info: false,
        add_members: false,
        remove_members: false,
        manage_admins: false,
      }
    }
  },
)

function submit() {
  if (mode.value === 'member') {
    emit('save', { role: 'member', permissions: null })
    return
  }

  if (mode.value === 'moderator') {
    emit('save', { role: 'moderator', permissions: null })
    return
  }

  if (mode.value === 'full') {
    emit('save', { role: 'admin', permissions: null })
    return
  }

  emit('save', { role: 'admin', permissions: { ...permissions.value } })
}

function togglePermission(key: GroupPermissionKey) {
  permissions.value = {
    ...permissions.value,
    [key]: !permissions.value[key],
  }
}

const roleOptions: { id: RoleMode; label: string; show?: boolean }[] = [
  { id: 'full', label: 'Full group admin', show: true },
  { id: 'limited', label: 'Limited admin', show: true },
  { id: 'moderator', label: 'Moderator', show: true },
  { id: 'member', label: 'Member', show: true },
]

const permissionOptions: { key: GroupPermissionKey; label: string }[] = [
  { key: 'edit_info', label: 'Edit group info' },
  { key: 'add_members', label: 'Add members' },
  { key: 'remove_members', label: 'Remove members' },
  { key: 'manage_admins', label: 'Manage admins' },
]
</script>

<template>
  <BaseModal
    :open="open"
    title="Member role"
    size="md"
    bare
    panel-class="chatify-role-modal"
    @close="emit('close')"
  >
    <div class="chatify:flex chatify:flex-col chatify:gap-4">
      <p class="chatify:text-sm chatify:text-chatify-muted">{{ memberName }}</p>

      <SettingsGroup>
        <button
          v-for="option in roleOptions"
          :key="option.id"
          v-show="option.id !== 'full' || canPromoteFullAdmin"
          type="button"
          class="chatify-settings-row chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:justify-between chatify:px-4 chatify:py-3 chatify:text-left"
          @click="mode = option.id"
        >
          <span class="chatify:text-sm">{{ option.label }}</span>
          <svg
            v-if="mode === option.id"
            class="chatify:h-4 chatify:w-4 chatify:text-chatify-primary"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </button>
      </SettingsGroup>

      <SettingsGroup v-if="mode === 'limited'" class="chatify:mt-1">
        <button
          v-for="option in permissionOptions"
          :key="option.key"
          type="button"
          class="chatify-settings-row chatify-list-item chatify:flex chatify:w-full chatify:items-center chatify:justify-between chatify:px-4 chatify:py-3 chatify:text-left"
          @click="togglePermission(option.key)"
        >
          <span class="chatify:text-sm">{{ option.label }}</span>
          <span
            role="switch"
            class="chatify-settings-toggle chatify:shrink-0"
            :class="permissions[option.key] ? 'chatify-settings-toggle-on' : ''"
            :aria-checked="permissions[option.key] ? 'true' : 'false'"
          />
        </button>
      </SettingsGroup>

      <div class="chatify-confirm-footer">
        <button type="button" class="chatify-btn-ghost" @click="emit('close')">
          Cancel
        </button>
        <button type="button" class="chatify-btn-ghost-primary" @click="submit">
          Save
        </button>
      </div>
    </div>
  </BaseModal>
</template>
