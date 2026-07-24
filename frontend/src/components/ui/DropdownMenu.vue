<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useChatifyDirection } from '../../composables/useChatifyDirection'
import { useChatifyI18n } from '../../composables/useChatifyI18n'
import ContextMenu, { type ContextMenuItem } from './ContextMenu.vue'
import { floatingMenuPositionFromRect } from '../../utils/floatingMenuPosition'

export interface DropdownMenuItem {
  id: string
  label: string
  danger?: boolean
  disabled?: boolean
}

const props = defineProps<{
  items: DropdownMenuItem[]
  align?: 'start' | 'end'
  disabled?: boolean
}>()

const emit = defineEmits<{
  select: [id: string]
}>()

const { t } = useChatifyI18n()
const { isRtl } = useChatifyDirection()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)
const menuX = ref(0)
const menuY = ref(0)

const contextItems = computed<ContextMenuItem[]>(() =>
  props.items.map((item) => ({
    id: item.id,
    label: item.label,
    danger: item.danger,
    disabled: item.disabled,
  })),
)

function toggle() {
  if (props.disabled || props.items.length === 0) {
    return
  }
  open.value = !open.value
}

function close() {
  open.value = false
}

function selectItem(id: string) {
  close()
  emit('select', id)
}

async function updatePosition() {
  const btn = trigger.value
  if (!btn) {
    return
  }

  await nextTick()

  const rect = btn.getBoundingClientRect()
  const menuWidth = 160
  const menuHeight = Math.max(1, props.items.length) * 40 + 16
  const position = floatingMenuPositionFromRect(rect, menuWidth, menuHeight, {
    align: props.align ?? 'end',
    isRtl: isRtl.value,
  })

  menuX.value = position.x
  menuY.value = position.y
}

function onViewportChange() {
  if (open.value) {
    void updatePosition()
  }
}

watch(open, async (isOpen) => {
  if (isOpen) {
    await updatePosition()
  }
})

onMounted(() => {
  window.addEventListener('resize', onViewportChange)
  window.addEventListener('scroll', onViewportChange, true)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
})

defineExpose({ close })
</script>

<template>
  <div ref="root" class="chatify:shrink-0">
    <button
      ref="trigger"
      type="button"
      class="chatify:flex chatify:h-8 chatify:w-8 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-chatify-muted chatify:transition chatify:hover:bg-chatify-sidebar chatify:hover:text-chatify-text disabled:chatify:opacity-40"
      :disabled="disabled || items.length === 0"
      :aria-label="t('ui.common.more_actions')"
      aria-haspopup="menu"
      :aria-expanded="open"
      @click.stop="toggle"
    >
      <slot name="trigger">
        <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </slot>
    </button>

    <ContextMenu
      :open="open"
      :x="menuX"
      :y="menuY"
      :items="contextItems"
      :ignore-root="root"
      @select="selectItem"
      @close="close"
    />
  </div>
</template>
