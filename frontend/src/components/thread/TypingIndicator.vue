<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useConversationsStore } from '../../stores/conversations'
import { useTypingStore } from '../../stores/typing'
import { useConfigStore } from '../../stores/config'

const conversationsStore = useConversationsStore()
const typingStore = useTypingStore()
const configStore = useConfigStore()
const { activeConversation, activeId } = storeToRefs(conversationsStore)

const typingNames = computed(() => {
  if (!activeId.value) {
    return []
  }

  const ids = typingStore.typingUserIds(activeId.value, configStore.user?.id)
  return ids.map((id) => {
    const participant = activeConversation.value?.relationships.participants.find(
      (user) => String(user.id) === id,
    )
    return participant?.attributes.name ?? 'Someone'
  })
})

const label = computed(() => {
  const names = typingNames.value
  if (names.length === 0) {
    return ''
  }
  if (names.length === 1) {
    return `${names[0]} is typing…`
  }
  return `${names.slice(0, 2).join(', ')} are typing…`
})
</script>

<template>
  <div
    v-if="label"
    class="chatify:px-4 chatify:pb-1 chatify:text-xs chatify:italic chatify:text-chatify-muted"
  >
    {{ label }}
  </div>
</template>
