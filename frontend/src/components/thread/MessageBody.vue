<script setup lang="ts">
import { computed, inject, nextTick, onMounted, ref, watch } from 'vue'
import { MESSAGE_SCROLL_PIN_KEY } from '../../constants/dom'
import { useConfigStore } from '../../stores/config'
import type { LinkPreview } from '../../types'
import { firstLinkUrl, linkify } from '../../utils/linkify'
import {
  getLinkPreview,
  hasLinkPreviewData,
  setLinkPreview,
} from '../../utils/linkPreviewCache'
import LinkPreviewCard from './LinkPreviewCard.vue'
import LinkPreviewSkeleton from './LinkPreviewSkeleton.vue'

const props = defineProps<{
  body: string
}>()

const configStore = useConfigStore()
const scrollPin = inject(MESSAGE_SCROLL_PIN_KEY, null)
const segments = ref(linkify(props.body))
const linkPreview = ref<LinkPreview | null>(null)
const previewLoading = ref(false)
const previewDismissed = ref(false)
let previewRequestId = 0

const previewUrl = computed(() => firstLinkUrl(props.body))

const showPreviewSlot = computed(() => {
  if (!previewUrl.value || previewDismissed.value) {
    return false
  }
  if (previewLoading.value) {
    return true
  }
  return hasLinkPreviewData(linkPreview.value)
})

function pinScrollIfNearBottom() {
  if (!scrollPin?.isNearBottom.value) {
    return
  }
  void nextTick(() => scrollPin.scrollToBottom('auto'))
}

async function loadPreview(url: string) {
  if (!configStore.api) {
    previewDismissed.value = true
    return
  }

  const requestId = ++previewRequestId
  previewLoading.value = true

  try {
    const { data } = await configStore.api.fetchLinkPreview(url)
    if (requestId !== previewRequestId) {
      return
    }

    setLinkPreview(url, data.data)

    if (hasLinkPreviewData(data.data)) {
      linkPreview.value = data.data
      pinScrollIfNearBottom()
      return
    }

    previewDismissed.value = true
  } catch {
    if (requestId === previewRequestId) {
      previewDismissed.value = true
    }
  } finally {
    if (requestId === previewRequestId) {
      previewLoading.value = false
    }
  }
}

function syncBody() {
  segments.value = linkify(props.body)
  linkPreview.value = null
  previewLoading.value = false
  previewDismissed.value = false
  previewRequestId += 1

  const url = firstLinkUrl(props.body)
  if (!url) {
    return
  }

  const cached = getLinkPreview(url)
  if (cached !== undefined) {
    if (hasLinkPreviewData(cached)) {
      linkPreview.value = cached
    } else {
      previewDismissed.value = true
    }
    return
  }

  void loadPreview(url)
}

onMounted(syncBody)

watch(
  () => props.body,
  () => syncBody(),
)
</script>

<template>
  <div>
    <p class="chatify-message-body chatify:whitespace-pre-wrap chatify:break-words chatify:text-sm">
      <template v-for="(segment, index) in segments" :key="index">
        <span v-if="segment.type === 'text'">{{ segment.value }}</span>
        <a
          v-else
          :href="segment.href"
          target="_blank"
          rel="noopener noreferrer"
          class="chatify:text-chatify-primary chatify:underline"
        >
          {{ segment.value }}
        </a>
      </template>
    </p>

    <template v-if="showPreviewSlot">
      <LinkPreviewSkeleton v-if="previewLoading" />
      <LinkPreviewCard
        v-else-if="linkPreview && hasLinkPreviewData(linkPreview)"
        :preview="linkPreview"
      />
    </template>
  </div>
</template>
