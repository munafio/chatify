<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'

export interface DropdownMenuItem {
  id: string
  label: string
  danger?: boolean
  disabled?: boolean
}

const props = defineProps<{
  items: DropdownMenuItem[]
  align?: 'start' | 'end'
}>()

const emit = defineEmits<{
  select: [id: string]
}>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)
const menu = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top: string; left: string }>({ top: '0px', left: '0px' })

function toggle() {
  if (props.items.length === 0) {
    return
  }
  open.value = !open.value
}

function close() {
  open.value = false
}

function selectItem(item: DropdownMenuItem) {
  if (item.disabled) {
    return
  }
  close()
  emit('select', item.id)
}

function updatePosition() {
  const btn = trigger.value
  const menuEl = menu.value
  if (!btn || !menuEl) {
    return
  }

  const rect = btn.getBoundingClientRect()
  const menuWidth = menuEl.offsetWidth
  const menuHeight = menuEl.offsetHeight
  const margin = 8
  const gap = 4

  let top = rect.bottom + gap
  if (top + menuHeight > window.innerHeight - margin) {
    top = rect.top - menuHeight - gap
  }
  if (top < margin) {
    top = margin
  }

  let left = props.align === 'end' ? rect.right - menuWidth : rect.left
  if (left + menuWidth > window.innerWidth - margin) {
    left = window.innerWidth - menuWidth - margin
  }
  if (left < margin) {
    left = margin
  }

  menuStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
  }
}

function onDocumentClick(event: MouseEvent) {
  if (!open.value) {
    return
  }

  if (root.value && !root.value.contains(event.target as Node) && !menu.value?.contains(event.target as Node)) {
    close()
  }
}

function onViewportChange() {
  if (open.value) {
    updatePosition()
  }
}

watch(open, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    updatePosition()
  }
})

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
  window.addEventListener('resize', onViewportChange)
  window.addEventListener('scroll', onViewportChange, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick)
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
      :disabled="items.length === 0"
      aria-label="More actions"
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

    <Teleport :to="CHATIFY_TELEPORT_TARGET">
      <div
        v-if="open"
        ref="menu"
        class="chatify-profile-menu chatify-profile-menu-floating"
        role="menu"
        :style="menuStyle"
      >
        <button
          v-for="item in items"
          :key="item.id"
          type="button"
          role="menuitem"
          class="chatify-profile-menu-item"
          :class="item.danger ? 'chatify-profile-menu-item-danger' : ''"
          :disabled="item.disabled"
          @click="selectItem(item)"
        >
          {{ item.label }}
        </button>
      </div>
    </Teleport>
  </div>
</template>
