<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps<{
  isOwn: boolean
}>()

const emit = defineEmits<{
  edit: []
  removeForMe: []
  removeForAll: []
  reply: []
  forward: []
}>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)
const menu = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top: string; left: string }>({ top: '0px', left: '0px' })

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
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

  let left = props.isOwn ? rect.left - menuWidth + rect.width : rect.left
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

  if (root.value && !root.value.contains(event.target as Node)) {
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
</script>

<template>
  <div ref="root" class="chatify:shrink-0">
    <button
      ref="trigger"
      type="button"
      class="chatify-message-actions-trigger chatify:flex chatify:h-7 chatify:w-7 chatify:items-center chatify:justify-center chatify:rounded-full chatify:text-chatify-muted chatify:opacity-0 chatify:transition chatify:group-hover:opacity-100"
      aria-label="Message actions"
      @click.stop="toggle"
    >
      <svg class="chatify:h-4 chatify:w-4" fill="currentColor" viewBox="0 0 24 24">
        <circle cx="5" cy="12" r="1.75" />
        <circle cx="12" cy="12" r="1.75" />
        <circle cx="19" cy="12" r="1.75" />
      </svg>
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="menu"
        class="chatify-profile-menu chatify-profile-menu-floating"
        :style="menuStyle"
      >
        <template v-if="isOwn">
          <button type="button" class="chatify-profile-menu-item" @click="emit('edit'); close()">Edit</button>
          <button type="button" class="chatify-profile-menu-item" @click="emit('removeForMe'); close()">Remove for me</button>
          <button type="button" class="chatify-profile-menu-item chatify-profile-menu-item-danger" @click="emit('removeForAll'); close()">Remove for everyone</button>
        </template>
        <template v-else>
          <button type="button" class="chatify-profile-menu-item" @click="emit('reply'); close()">Reply</button>
          <button type="button" class="chatify-profile-menu-item" @click="emit('forward'); close()">Forward</button>
          <button type="button" class="chatify-profile-menu-item" @click="emit('removeForMe'); close()">Remove for me</button>
        </template>
      </div>
    </Teleport>
  </div>
</template>
