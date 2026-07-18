import { computed } from 'vue'
import { useConfigStore } from '../stores/config'

export interface GiphySticker {
  id: string
  title: string
  previewUrl: string
  fullUrl: string
}

interface GiphyImageRendition {
  url: string
  width: string
  height: string
}

interface GiphyStickerItem {
  id: string
  title: string
  images: {
    fixed_height?: GiphyImageRendition
    downsized?: GiphyImageRendition
    preview_gif?: GiphyImageRendition
  }
}

interface GiphyResponse {
  data: GiphyStickerItem[]
}

function mapSticker(item: GiphyStickerItem): GiphySticker {
  const previewUrl =
    item.images.preview_gif?.url
    ?? item.images.fixed_height?.url
    ?? item.images.downsized?.url
    ?? ''

  const fullUrl =
    item.images.downsized?.url
    ?? item.images.fixed_height?.url
    ?? previewUrl

  return {
    id: item.id,
    title: item.title || 'Sticker',
    previewUrl,
    fullUrl,
  }
}

export function useGiphy() {
  const configStore = useConfigStore()

  const apiKey = computed(() => configStore.boot?.giphy?.apiKey ?? null)
  const isEnabled = computed(() => configStore.giphyEnabled)

  async function requestStickers(path: string): Promise<GiphySticker[]> {
    if (!apiKey.value) {
      return []
    }

    const url = new URL(`https://api.giphy.com/v1/stickers/${path}`)
    url.searchParams.set('api_key', apiKey.value)
    url.searchParams.set('limit', '24')
    url.searchParams.set('rating', 'g')

    const response = await fetch(url.toString())
    if (!response.ok) {
      throw new Error('Failed to load stickers')
    }

    const payload = (await response.json()) as GiphyResponse
    return payload.data.map(mapSticker).filter((item) => item.previewUrl && item.fullUrl)
  }

  async function searchStickers(query: string): Promise<GiphySticker[]> {
    const trimmed = query.trim()
    if (!trimmed) {
      return trendingStickers()
    }

    const url = `search?${new URLSearchParams({ q: trimmed }).toString()}`
    return requestStickers(url)
  }

  async function trendingStickers(): Promise<GiphySticker[]> {
    return requestStickers('trending')
  }

  async function fetchStickerAsFile(sticker: GiphySticker): Promise<File> {
    const response = await fetch(sticker.fullUrl)
    if (!response.ok) {
      throw new Error('Failed to download sticker')
    }

    const blob = await response.blob()
    const extension = blob.type.includes('webp') ? 'webp' : 'gif'
    const safeName = sticker.title.replace(/[^\w.-]+/g, '_').slice(0, 48) || 'sticker'

    return new File([blob], `${safeName}.${extension}`, {
      type: blob.type || 'image/gif',
    })
  }

  return {
    apiKey,
    isEnabled,
    searchStickers,
    trendingStickers,
    fetchStickerAsFile,
  }
}
