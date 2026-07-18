<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useConfirmStore } from '../../stores/confirm'
import BaseModal from '../modals/BaseModal.vue'

const confirmStore = useConfirmStore()
const { options } = storeToRefs(confirmStore)

const open = computed(() => options.value !== null)

const title = computed(() => options.value?.title ?? 'Confirm')
const message = computed(() => options.value?.message ?? '')
const confirmLabel = computed(() => options.value?.confirmLabel ?? 'Confirm')
const cancelLabel = computed(() => options.value?.cancelLabel ?? 'Cancel')
const isDanger = computed(() => options.value?.variant === 'danger')

function cancel() {
  confirmStore.answer(false)
}

function confirm() {
  confirmStore.answer(true)
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="title"
    size="sm"
    bare
    panel-class="chatify-confirm-modal"
    @close="cancel"
  >
    <div class="chatify:flex chatify:flex-col chatify:gap-4">
      <p v-if="message" class="chatify:text-sm chatify:text-chatify-muted">
        {{ message }}
      </p>

      <div class="chatify-confirm-footer">
        <button type="button" class="chatify-btn-ghost" @click="cancel">
          {{ cancelLabel }}
        </button>
        <button
          type="button"
          class="chatify:flex-1"
          :class="isDanger ? 'chatify-btn-ghost-danger' : 'chatify-btn-ghost-primary'"
          @click="confirm"
        >
          {{ confirmLabel }}
        </button>
      </div>
    </div>
  </BaseModal>
</template>
