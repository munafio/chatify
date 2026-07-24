<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import type { ChatifyConversation, MessageAttachment, SharedAttachment } from '../../types'
import { useInfiniteScroll } from '../../composables/useInfiniteScroll'
import { useImageLightbox } from '../../composables/useImageLightbox'
import { useConfigStore } from '../../stores/config'
import { useUiStore } from '../../stores/ui'
import { jumpToMessage } from '../../utils/jumpToMessage'
import EmptyState from '../states/EmptyState.vue'
import GroupMediaSkeleton from '../skeletons/GroupMediaSkeleton.vue'
import { useChatifyI18n } from '../../composables/useChatifyI18n'

const props = defineProps<{
  conversation: ChatifyConversation
}>()

const configStore = useConfigStore()
const uiStore = useUiStore()
const { show } = useImageLightbox()
const { t } = useChatifyI18n()

function isImageItem(item: SharedAttachment): boolean {
  const mime = item.attributes.mime ?? ''
  if (mime.startsWith('image/')) {
    return true
  }
  const url = item.attributes.url ?? ''
  return /\.(png|jpe?g|gif|webp|bmp|svg|avif)$/i.test(url)
}

function toMessageAttachment(item: SharedAttachment): MessageAttachment {
  return {
    filename: item.attributes.filename ?? '',
    original_name: item.attributes.original_name,
    type: 'image',
    url: item.attributes.url ?? '',
  }
}

const imageItems = computed(() => items.value.filter(isImageItem))

function openMedia(item: SharedAttachment) {
  if (isImageItem(item)) {
    const gallery = imageItems.value
    const index = gallery.findIndex(
      (candidate) =>
        candidate.attributes.message_id === item.attributes.message_id &&
        candidate.attributes.url === item.attributes.url,
    )
    show(gallery.map(toMessageAttachment), Math.max(0, index))
    return
  }

  goToMessage(item)
}

function goToMessage(item: SharedAttachment) {
  const messageId = item.attributes.message_id
  if (!messageId) {
    return
  }
  uiStore.closeModal()
  void nextTick(() => {
    jumpToMessage(messageId)
  })
}
const tab = ref<'media' | 'docs' | 'links'>('media')
const items = ref<SharedAttachment[]>([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(false)
const initialLoaded = ref(false)

const emptyCopy = computed(() => {
  switch (tab.value) {
    case 'docs':
      return { title: t('ui.group.media.empty.docs_title'), description: t('ui.group.media.empty.docs_description') }
    case 'links':
      return { title: t('ui.group.media.empty.links_title'), description: t('ui.group.media.empty.links_description') }
    default:
      return { title: t('ui.group.media.empty.media_title'), description: t('ui.group.media.empty.media_description') }
  }
})

const tabOptions = computed(() => [
  ['media', t('ui.group.media.tabs.media')] as const,
  ['docs', t('ui.group.media.tabs.docs')] as const,
  ['links', t('ui.group.media.tabs.links')] as const,
])

async function load(reset = false) {
  if (!configStore.api || loading.value) {
    return
  }

  if (reset) {
    page.value = 1
    lastPage.value = 1
    items.value = []
    initialLoaded.value = false
  }

  if (page.value > lastPage.value && !reset) {
    return
  }

  loading.value = true
  try {
    const { data } = await configStore.api.getAttachments(props.conversation.id, {
      type: tab.value,
      page: page.value,
      per_page: 24,
    })

    items.value = reset
      ? data.data
      : [...items.value, ...data.data.filter((item) => {
          const key = `${item.attributes.message_id}-${item.attributes.url}`
          return !items.value.some(
            (existing) => `${existing.attributes.message_id}-${existing.attributes.url}` === key,
          )
        })]
    lastPage.value = data.meta?.last_page ?? 1
    page.value += 1
    initialLoaded.value = true
  } finally {
    loading.value = false
  }
}

const { sentinel } = useInfiniteScroll(async () => {
  if (initialLoaded.value) {
    await load(false)
  }
})

watch(tab, () => {
  void load(true)
})

watch(
  () => props.conversation.id,
  () => {
    tab.value = 'media'
    void load(true)
  },
  { immediate: true },
)
</script>

<template>
  <div class="chatify:flex chatify:min-h-0 chatify:flex-1 chatify:flex-col">
    <div class="chatify:mb-3 chatify:flex chatify:gap-4 chatify:border-b chatify:border-chatify-border">
      <button
        v-for="option in tabOptions"
        :key="option[0]"
        type="button"
        class="chatify:border-b-2 chatify:px-1 chatify:pb-2 chatify:text-sm"
        :class="tab === option[0] ? 'chatify:border-chatify-primary chatify:text-chatify-text' : 'chatify:border-transparent chatify:text-chatify-muted'"
        @click="tab = option[0]"
      >
        {{ option[1] }}
      </button>
    </div>

    <div class="chatify:min-h-0 chatify:flex-1 chatify:overflow-y-auto">
      <GroupMediaSkeleton v-if="loading && !initialLoaded" />

      <template v-else-if="items.length > 0">
        <div v-if="tab === 'media'" class="chatify:grid chatify:grid-cols-3 chatify:gap-1">
          <button
            v-for="item in items"
            :key="`${item.attributes.message_id}-${item.attributes.url}`"
            type="button"
            class="chatify:group chatify:relative chatify:aspect-square chatify:overflow-hidden chatify:bg-chatify-sidebar chatify:transition"
            @click="openMedia(item)"
          >
            <img
              :src="item.attributes.url ?? ''"
              alt=""
              class="chatify:h-full chatify:w-full chatify:object-cover chatify:transition group-hover:chatify:opacity-80"
            />
          </button>
        </div>

        <ul v-else class="chatify:flex chatify:flex-col chatify:gap-1">
          <template v-if="tab === 'links'">
            <li v-for="item in items" :key="`${item.attributes.message_id}-${item.attributes.url}`">
              <a
                :href="item.attributes.url ?? '#'"
                target="_blank"
                rel="noopener"
                class="chatify:block chatify:rounded-lg chatify:px-2 chatify:py-3 chatify:transition chatify:hover:bg-chatify-sidebar"
              >
                <span class="chatify:block chatify:truncate chatify:text-sm chatify:text-chatify-primary">
                  {{ item.attributes.url }}
                </span>
                <span v-if="item.attributes.snippet" class="chatify:mt-1 chatify:block chatify:text-xs chatify:text-chatify-muted">
                  {{ item.attributes.snippet }}
                </span>
              </a>
            </li>
          </template>

          <template v-else>
            <li v-for="item in items" :key="`${item.attributes.message_id}-${item.attributes.url}`">
              <button
                type="button"
                class="chatify:flex chatify:w-full chatify:items-center chatify:gap-3 chatify:rounded-lg chatify:px-2 chatify:py-3 chatify:text-start chatify:transition chatify:hover:bg-chatify-sidebar"
                @click="goToMessage(item)"
              >
                <span class="chatify:flex chatify:h-9 chatify:w-9 chatify:shrink-0 chatify:items-center chatify:justify-center chatify:rounded-md chatify:bg-chatify-sidebar chatify:text-chatify-muted">
                  <svg class="chatify:h-5 chatify:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </span>
                <span class="chatify:min-w-0 chatify:flex-1 chatify:truncate chatify:text-sm chatify:text-chatify-text">
                  {{ item.attributes.original_name || item.attributes.filename || t('ui.group.media.document') }}
                </span>
              </button>
            </li>
          </template>
        </ul>
      </template>

      <EmptyState
        v-else-if="initialLoaded"
        :title="emptyCopy.title"
        :description="emptyCopy.description"
      />

      <div ref="sentinel" class="chatify:h-4" />
    </div>
  </div>
</template>
