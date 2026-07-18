<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useConfigStore } from '../../stores/config'
import type { LinkPreview } from '../../types'
import { firstLinkUrl, linkify } from '../../utils/linkify'
import LinkPreviewCard from './LinkPreviewCard.vue'

const props = defineProps<{
  body: string
}>()

const configStore = useConfigStore()
const segments = ref(linkify(props.body))
const linkPreview = ref<LinkPreview | null>(null)
const previewLoading = ref(false)
let previewRequestId = 0

const hasPreviewData = computed(() => {
  const preview = linkPreview.value
  if (!preview) {
    return false
  }
  return Boolean(preview.title || preview.description || preview.image)
})

async function loadPreview(url: string) {
  if (!configStore.api) {
    return
  }

  const requestId = ++previewRequestId
  previewLoading.value = true

  try {
    const { data } = await configStore.api.fetchLinkPreview(url)
    if (requestId === previewRequestId) {
      linkPreview.value = data.data
    }
  } catch {
    if (requestId === previewRequestId) {
      linkPreview.value = null
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

  const url = firstLinkUrl(props.body)
  if (url) {
    void loadPreview(url)
  }
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

    <LinkPreviewCard v-if="linkPreview && hasPreviewData" :preview="linkPreview" />
  </div>
</template>
