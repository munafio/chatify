<script setup lang="ts">
import { useConversationsStore } from '../../stores/conversations'

const conversationsStore = useConversationsStore()

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  conversationsStore.setSearchQuery(target.value)
}
</script>

<template>
  <div class="chatify-sidebar-divide chatify:border-b chatify:px-3 chatify:py-2">
    <input
      type="search"
      placeholder="Search conversations"
      class="chatify-sidebar-input chatify:w-full chatify:rounded-lg chatify:px-3 chatify:py-2 chatify:text-sm chatify:text-chatify-text"
      @input="onInput"
    />
  </div>
</template>
