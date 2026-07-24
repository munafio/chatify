<script setup lang="ts">
import { ref } from 'vue'
import { useChatifyDirection } from '../../composables/useChatifyDirection'

defineProps<{
  placeholder: string
  clearLabel: string
  inputClass?: string
}>()

const model = defineModel<string>({ required: true })

const { dir } = useChatifyDirection()
const inputRef = ref<HTMLInputElement | null>(null)

function clear() {
  model.value = ''
  inputRef.value?.focus()
}

defineExpose({
  focus: () => inputRef.value?.focus(),
})
</script>

<template>
  <div class="chatify:relative chatify:min-w-0" :dir="dir">
    <svg
      class="chatify:pointer-events-none chatify:absolute chatify:top-1/2 chatify:start-3 chatify:z-10 chatify:h-4 chatify:w-4 chatify:-translate-y-1/2 chatify:text-chatify-muted"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
    </svg>

    <input
      ref="inputRef"
      v-model="model"
      type="search"
      :dir="dir"
      :placeholder="placeholder"
      class="chatify:w-full chatify:ps-9 chatify:text-start"
      :class="[
        model.trim() ? 'chatify:pe-9' : 'chatify:pe-3',
        inputClass,
      ]"
    />

    <button
      v-if="model"
      type="button"
      class="chatify:absolute chatify:top-1/2 chatify:end-2 chatify:z-10 chatify:-translate-y-1/2 chatify:rounded-full chatify:p-1 chatify:text-chatify-muted chatify:transition chatify:hover:bg-chatify-sidebar"
      :aria-label="clearLabel"
      @click="clear"
    >
      <svg class="chatify:h-4 chatify:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>
