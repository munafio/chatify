<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import BaseModal from './BaseModal.vue'
import SettingsNavShell from '../settings/SettingsNavShell.vue'
import SettingsRootView from '../settings/SettingsRootView.vue'
import SettingsThemesView from '../settings/SettingsThemesView.vue'
import SettingsFontView from '../settings/SettingsFontView.vue'
import SettingsWallpaperView from '../settings/SettingsWallpaperView.vue'
import SettingsBlockedContactsView from '../settings/SettingsBlockedContactsView.vue'
import { clonePreferences } from '../../composables/useBootConfig'
import type { ChatifyThemePreferences } from '../../themes/types'
import { useConfigStore } from '../../stores/config'
import { useSettingsStore } from '../../stores/settings'
import { useUiStore } from '../../stores/ui'

type SettingsScreen = 'root' | 'themes' | 'font' | 'wallpaper' | 'blocked'

const uiStore = useUiStore()
const configStore = useConfigStore()
const settingsStore = useSettingsStore()
const { activeModal } = storeToRefs(uiStore)
const { saving } = storeToRefs(settingsStore)
const { isDirty } = storeToRefs(configStore)

const screen = ref<SettingsScreen>('root')
const screenStack = ref<SettingsScreen[]>(['root'])
const direction = ref<'forward' | 'back'>('forward')
const draftSnapshots = ref<ChatifyThemePreferences[]>([])

const open = computed(() => activeModal.value === 'settings')

const titles: Record<SettingsScreen, string> = {
  root: 'Settings',
  themes: 'Themes',
  font: 'Choose font family',
  wallpaper: 'Wallpaper',
  blocked: 'Blocked contacts',
}

const screensWithFooter = new Set<SettingsScreen>(['themes', 'font', 'wallpaper'])

watch(open, (isOpen) => {
  if (isOpen) {
    configStore.beginDraftSession()
    screen.value = 'root'
    screenStack.value = ['root']
    draftSnapshots.value = []
  }
})

function navigate(next: SettingsScreen) {
  draftSnapshots.value.push(clonePreferences(configStore.draftPreferences))
  direction.value = 'forward'
  screenStack.value.push(next)
  screen.value = next
}

function goBack() {
  if (screenStack.value.length <= 1) {
    return
  }

  const snapshot = draftSnapshots.value.pop()
  if (snapshot) {
    configStore.restoreDraft(snapshot)
  }

  direction.value = 'back'
  screenStack.value.pop()
  screen.value = screenStack.value[screenStack.value.length - 1] ?? 'root'
}

function closeModal() {
  configStore.resetDraft()
  uiStore.closeModal()
}

function cancelChanges() {
  configStore.resetDraft()
  if (screen.value === 'root') {
    uiStore.closeModal()
    return
  }

  draftSnapshots.value = []
  screen.value = 'root'
  screenStack.value = ['root']
}

async function saveChanges() {
  const ok = await settingsStore.saveAll()
  if (ok) {
    draftSnapshots.value = []
    if (screen.value !== 'root') {
      screen.value = 'root'
      screenStack.value = ['root']
    }
  }
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Settings"
    size="md"
    bare
    panel-class="chatify-settings-modal"
    @close="closeModal"
  >
    <SettingsNavShell
      :title="titles[screen]"
      :show-back="screen !== 'root'"
      :show-footer="screensWithFooter.has(screen)"
      @back="goBack"
      @close="closeModal"
      @cancel="cancelChanges"
      @save="saveChanges"
    >
      <Transition :name="direction === 'forward' ? 'chatify-slide-left' : 'chatify-slide-right'" mode="out-in">
        <div :key="screen" class="chatify:min-w-0 chatify:overflow-hidden">
          <SettingsRootView v-if="screen === 'root'" @navigate="navigate" />
          <SettingsThemesView v-else-if="screen === 'themes'" />
          <SettingsFontView v-else-if="screen === 'font'" />
          <SettingsWallpaperView v-else-if="screen === 'wallpaper'" />
          <SettingsBlockedContactsView v-else-if="screen === 'blocked'" />
        </div>
      </Transition>

      <template #footer>
        <button
          type="button"
          class="chatify:rounded-lg chatify:px-4 chatify:py-2 chatify:text-sm chatify:text-chatify-muted chatify:hover:bg-chatify-sidebar"
          :disabled="saving"
          @click="cancelChanges"
        >
          Cancel
        </button>
        <button
          type="button"
          class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-2 chatify:text-sm chatify:font-medium chatify:text-white chatify:disabled:opacity-50"
          :disabled="saving || !isDirty"
          @click="saveChanges"
        >
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
      </template>
    </SettingsNavShell>
  </BaseModal>
</template>
