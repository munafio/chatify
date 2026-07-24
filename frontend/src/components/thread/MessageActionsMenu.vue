<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import ContextMenu from '../ui/ContextMenu.vue'
import {
  buildMessageActionItems,
  type MessageActionId,
} from '../../utils/messageActionItems'

const props = defineProps<{
  isOwn: boolean
  hasCopyableText: boolean
  isSavedConversation?: boolean
}>()

const emit = defineEmits<{
  edit: []
  removeForMe: []
  removeForAll: []
  reply: []
  forward: []
  copy: []
}>()

const { t } = useChatifyI18n()
const open = ref(false)
const menuX = ref(0)
const menuY = ref(0)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)

const items = computed(() =>
  buildMessageActionItems({
    isOwn: props.isOwn,
    hasCopyableText: props.hasCopyableText,
    isSavedConversation: props.isSavedConversation,
  }),
)

function close() {
  open.value = false
}

function emitAction(id: MessageActionId) {
  switch (id) {
    case 'copy':
      emit('copy')
      break
    case 'edit':
      emit('edit')
      break
    case 'reply':
      emit('reply')
      break
    case 'forward':
      emit('forward')
      break
    case 'removeForMe':
      emit('removeForMe')
      break
    case 'removeForAll':
      emit('removeForAll')
      break
  }
}

async function openAt(x: number, y: number) {
  menuX.value = x
  menuY.value = y
  open.value = true
}

async function openFromTrigger() {
  const btn = trigger.value
  if (!btn) {
    return
  }

  const rect = btn.getBoundingClientRect()
  menuX.value = props.isOwn ? rect.right : rect.left
  menuY.value = rect.bottom + 4
  open.value = true
  await nextTick()
}

function toggle() {
  if (open.value) {
    close()
    return
  }

  void openFromTrigger()
}

function onContextMenuSelect(id: string) {
  emitAction(id as MessageActionId)
}

defineExpose({ openAt, close })
</script>

<template>
  <div ref="root" class="chatify:shrink-0">
    <button
      ref="trigger"
      type="button"
      class="chatify-message-actions-trigger chatify:flex chatify:h-7 chatify:w-7 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-chatify-muted chatify:opacity-0 chatify:transition chatify:group-hover:opacity-100"
      :class="open ? 'chatify:opacity-100' : ''"
      :aria-label="t('ui.thread.bubble.message_actions')"
      aria-haspopup="menu"
      :aria-expanded="open"
      @click.stop="toggle"
    >
      <svg class="chatify:h-4 chatify:w-4" fill="currentColor" viewBox="0 0 24 24">
        <circle cx="5" cy="12" r="1.75" />
        <circle cx="12" cy="12" r="1.75" />
        <circle cx="19" cy="12" r="1.75" />
      </svg>
    </button>

    <ContextMenu
      :open="open"
      :x="menuX"
      :y="menuY"
      :items="items"
      :ignore-root="root"
      @select="onContextMenuSelect"
      @close="close"
    />
  </div>
</template>
