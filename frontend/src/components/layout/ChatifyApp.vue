<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useBreakpoints } from '../../composables/useBreakpoints'
import { useUiStore } from '../../stores/ui'
import Sidebar from '../sidebar/Sidebar.vue'
import ThreadPanel from '../thread/ThreadPanel.vue'

const { isMobile } = useBreakpoints()
const uiStore = useUiStore()
const { showThreadOnMobile } = storeToRefs(uiStore)
</script>

<template>
  <div class="chatify:flex chatify:h-full chatify:overflow-hidden chatify:bg-chatify-sidebar">
    <aside
      class="chatify:flex chatify:h-full chatify:flex-col chatify:border-e chatify:bg-chatify-sidebar chatify-sidebar-divide"
      :class="[
        isMobile ? 'chatify:w-full' : 'chatify:w-[380px] chatify:shrink-0',
        isMobile && showThreadOnMobile ? 'chatify:hidden' : 'chatify:flex',
      ]"
    >
      <Sidebar />
    </aside>

    <main
      dir="ltr"
      class="chatify-thread-panel chatify:flex chatify:min-w-0 chatify:flex-1 chatify:flex-col chatify:bg-chatify-panel"
      :class="[
        isMobile ? 'chatify:w-full' : '',
        isMobile && !showThreadOnMobile ? 'chatify:hidden' : 'chatify:flex',
      ]"
    >
      <ThreadPanel />
    </main>
  </div>
</template>
