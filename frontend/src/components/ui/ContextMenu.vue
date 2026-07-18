<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { CHATIFY_TELEPORT_TARGET } from '../../constants/dom'

export interface ContextMenuItem {
  id: string
  label: string
  danger?: boolean
  disabled?: boolean
  separatorBefore?: boolean
}

const props = defineProps<{
  open: boolean
  x: number
  y: number
  items: ContextMenuItem[]
  ignoreRoot?: HTMLElement | null
}>()

const emit = defineEmits<{
  select: [id: string]
  close: []
}>()

const menu = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top: string; left: string }>({ top: '0px', left: '0px' })

function close() {
  emit('close')
}

function selectItem(item: ContextMenuItem) {
  if (item.disabled) {
    return
  }
  emit('select', item.id)
  close()
}

function updatePosition() {
  const menuEl = menu.value
  if (!menuEl) {
    return
  }

  const menuWidth = menuEl.offsetWidth
  const menuHeight = menuEl.offsetHeight
  const margin = 8

  let left = props.x
  let top = props.y

  if (left + menuWidth > window.innerWidth - margin) {
    left = window.innerWidth - menuWidth - margin
  }
  if (left < margin) {
    left = margin
  }

  if (top + menuHeight > window.innerHeight - margin) {
    top = props.y - menuHeight
  }
  if (top < margin) {
    top = margin
  }

  menuStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
  }
}

function onDocumentPointerDown(event: MouseEvent) {
  if (!props.open) {
    return
  }

  const target = event.target as Node

  if (props.ignoreRoot?.contains(target)) {
    return
  }

  if (menu.value?.contains(target)) {
    return
  }

  close()
}

function onDocumentKeyDown(event: KeyboardEvent) {
  if (props.open && event.key === 'Escape') {
    event.preventDefault()
    close()
  }
}

function onViewportChange() {
  if (props.open) {
    updatePosition()
  }
}

watch(
  () => [props.open, props.x, props.y] as const,
  async ([isOpen]) => {
    if (isOpen) {
      await nextTick()
      updatePosition()
    }
  },
)

onMounted(() => {
  document.addEventListener('mousedown', onDocumentPointerDown)
  document.addEventListener('keydown', onDocumentKeyDown)
  window.addEventListener('resize', onViewportChange)
  window.addEventListener('scroll', onViewportChange, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentPointerDown)
  document.removeEventListener('keydown', onDocumentKeyDown)
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
})
</script>

<template>
  <Teleport :to="CHATIFY_TELEPORT_TARGET">
    <Transition name="chatify-context-menu">
      <div
        v-if="open && items.length > 0"
        ref="menu"
        class="chatify-context-menu"
        role="menu"
        :style="menuStyle"
        @contextmenu.prevent
      >
        <template v-for="item in items" :key="item.id">
          <div
            v-if="item.separatorBefore"
            class="chatify-context-menu-separator"
            role="separator"
          />
          <button
            type="button"
            role="menuitem"
            class="chatify-context-menu-item"
            :class="item.danger ? 'chatify-context-menu-item-danger' : ''"
            :disabled="item.disabled"
            @click="selectItem(item)"
          >
            {{ item.label }}
          </button>
        </template>
      </div>
    </Transition>
  </Teleport>
</template>
