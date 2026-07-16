<script setup lang="ts">
defineProps<{
  title: string
  showBack?: boolean
  showFooter?: boolean
}>()

defineEmits<{
  back: []
  close: []
  cancel: []
  save: []
}>()
</script>

<template>
  <div class="chatify:flex chatify:h-full chatify:min-h-0 chatify:min-w-0 chatify:flex-1 chatify:flex-col chatify:overflow-hidden">
    <header class="chatify:mb-2 chatify:flex chatify:shrink-0 chatify:items-center chatify:gap-2">
      <button
        v-if="showBack"
        type="button"
        class="chatify:rounded-full chatify:p-1 chatify:text-chatify-text chatify:hover:bg-black/10"
        aria-label="Back"
        @click="$emit('back')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <h3 class="chatify:flex-1 chatify:text-sm chatify:font-semibold chatify:text-chatify-text">{{ title }}</h3>
      <button
        type="button"
        class="chatify:rounded-full chatify:p-1 chatify:text-chatify-muted chatify:hover:bg-black/10"
        aria-label="Close settings"
        @click="$emit('close')"
      >
        <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </header>

    <div class="chatify-settings-scroll chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto">
      <slot />
    </div>

    <footer
      v-if="showFooter"
      class="chatify:mt-2 chatify:flex chatify:shrink-0 chatify:items-center chatify:justify-end chatify:gap-2 chatify:pt-2"
    >
      <slot name="footer">
        <button
          type="button"
          class="chatify:rounded-lg chatify:px-4 chatify:py-2 chatify:text-sm chatify:text-chatify-muted chatify:hover:bg-chatify-sidebar"
          @click="$emit('cancel')"
        >
          Cancel
        </button>
        <button
          type="button"
          class="chatify:rounded-lg chatify:bg-chatify-primary chatify:px-4 chatify:py-2 chatify:text-sm chatify:font-medium chatify:text-white"
          @click="$emit('save')"
        >
          Save
        </button>
      </slot>
    </footer>
  </div>
</template>
