<script setup lang="ts">
import { nextTick, onMounted, provide, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { useComposerDrop } from '../../composables/useComposerDrop'
import { useConversationsStore } from '../../stores/conversations'
import { useUiStore } from '../../stores/ui'
import { THREAD_ROOT_KEY } from '../../constants/dom'
import EmptyState from '../states/EmptyState.vue'
import MessageComposer from './MessageComposer.vue'
import MessageList from './MessageList.vue'
import ThreadHeader from './ThreadHeader.vue'
import ThreadMessageSearch from './ThreadMessageSearch.vue'
import ThreadHeaderSkeleton from '../skeletons/ThreadHeaderSkeleton.vue'

const conversationsStore = useConversationsStore()
const uiStore = useUiStore()
const { isMobile } = useBreakpoints()
const { activeConversation, activeId } = storeToRefs(conversationsStore)
const { messageSearchOpen } = storeToRefs(uiStore)

const threadContainerRef = ref<HTMLElement | null>(null)
const composerRef = ref<InstanceType<typeof MessageComposer> | null>(null)

provide(THREAD_ROOT_KEY, threadContainerRef)

const { isDragging, dropKind, bind, unbind } = useComposerDrop(threadContainerRef, ({ files }) => {
  composerRef.value?.addFiles(files)
})

function goBack() {
  uiStore.hideThread()
  conversationsStore.clearActive()
}

watch(activeId, async (id) => {
  await nextTick()
  unbind()
  if (id) {
    bind()
  }
})

onMounted(async () => {
  await nextTick()
  if (activeId.value) {
    bind()
  }
})
</script>

<template>
  <EmptyState
    v-if="!activeId"
    title="Select a conversation"
    description="Choose a chat from the sidebar or start a new one."
    class="chatify:flex-1"
  />

  <ThreadMessageSearch v-else-if="messageSearchOpen" />

  <template v-else>
    <div
      ref="threadContainerRef"
      class="chatify:relative chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col"
      @contextmenu.prevent
    >
      <ThreadHeaderSkeleton v-if="!activeConversation" />
      <ThreadHeader
        v-else
        :conversation="activeConversation"
        :show-back="isMobile"
        @back="goBack"
      />

      <MessageList class="chatify-composer-thread-list" />

      <Transition name="chatify-drop-overlay">
        <div
          v-if="isDragging"
          class="chatify-composer-drop-overlay"
          aria-hidden="true"
        >
          <div class="chatify-composer-drop-card">
            <svg class="chatify:h-8 chatify:w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span>
              {{
                dropKind === 'document'
                  ? 'Drop document to send'
                  : dropKind === 'mixed'
                    ? 'Drop files to send'
                    : 'Drop photos or videos to send'
              }}
            </span>
          </div>
        </div>
      </Transition>

      <MessageComposer
        v-if="activeConversation"
        ref="composerRef"
        :conversation-id="activeConversation.id"
        class="chatify:absolute chatify:bottom-3 chatify:inset-x-3 chatify:z-30"
      />
    </div>
  </template>
</template>
