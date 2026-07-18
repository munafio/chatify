import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { createApiClient, createChatifyApi } from '../api/client'
import {
  applyBootCatalog,
  applyPreferences,
  applyThemeColors,
  clonePreferences,
  hydratePreferencesFromBoot,
  loadPreferences,
  savePreferences,
} from '../composables/useBootConfig'
import { fontOptions } from '../themes/fonts'
import { getThemeById, themePresets } from '../themes/presets'
import type { ChatifyThemePreferences, WallpaperPreferences } from '../themes/types'
import type { BootConfig } from '../types'
import { extractErrorMessage } from '../utils/errors'
import { useToastStore } from './toast'
import { wallpaperPatterns } from '../themes/patterns'

function cacheBustUrl(url: string): string {
  return `${url}${url.includes('?') ? '&' : '?'}t=${Date.now()}`
}

export const useConfigStore = defineStore('config', () => {
  const boot = ref<BootConfig | null>(null)
  const api = ref<ReturnType<typeof createChatifyApi> | null>(null)
  const savedPreferences = ref<ChatifyThemePreferences>(loadPreferences())
  const draftPreferences = ref<ChatifyThemePreferences>(clonePreferences(savedPreferences.value))
  const pendingWallpaperFile = ref<File | null>(null)
  const pendingWallpaperPreview = ref<string | null>(null)
  const savedAvatarUrl = ref<string | null>(null)
  const avatarUploadProgress = ref<number | null>(null)
  const avatarRemoving = ref(false)
  let activeAvatarPreviewUrl: string | null = null

  const preferences = computed(() => draftPreferences.value)
  const isDirty = computed(() => JSON.stringify(savedPreferences.value) !== JSON.stringify(draftPreferences.value)
    || pendingWallpaperFile.value !== null)

  const avatarUploading = computed(() => avatarUploadProgress.value !== null || avatarRemoving.value)

  const user = computed(() => boot.value?.user ?? null)
  const groupsEnabled = computed(() => boot.value?.groupsEnabled ?? false)
  const colors = computed(() => boot.value?.colors ?? [])
  const features = computed(() => boot.value?.features ?? {
    giphy: false,
    colors: true,
    themes: true,
    fonts: true,
    wallpaper: true,
  })
  const colorsEnabled = computed(() => features.value.colors)
  const themesEnabled = computed(() => features.value.themes)
  const fontsEnabled = computed(() => features.value.fonts)
  const wallpaperEnabled = computed(() => features.value.wallpaper)
  const giphyEnabled = computed(() => features.value.giphy)
  const themePresetsList = computed(() => themePresets())
  const fontOptionsList = computed(() => fontOptions())
  const wallpaperPatternsList = computed(() => wallpaperPatterns())
  const attachments = computed(() => boot.value?.attachments)
  const debug = computed(() => boot.value?.debug ?? false)
  const broadcastEnabled = computed(() => {
    const broadcast = boot.value?.broadcast
    return Boolean(
      broadcast?.key &&
        broadcast.driver !== 'null' &&
        broadcast.driver !== 'log',
    )
  })

  function revokeActiveAvatarPreview() {
    if (activeAvatarPreviewUrl) {
      URL.revokeObjectURL(activeAvatarPreviewUrl)
      activeAvatarPreviewUrl = null
    }
  }

  function setUserAvatar(url: string) {
    if (boot.value?.user) {
      boot.value.user.attributes.avatar = url
    }
  }

  function init(config: BootConfig) {
    boot.value = config
    applyBootCatalog(config)
    const client = createApiClient(config)
    api.value = createChatifyApi(client)

    savedPreferences.value = hydratePreferencesFromBoot(config)
    draftPreferences.value = clonePreferences(savedPreferences.value)
    savedAvatarUrl.value = config.user?.attributes.avatar ?? null
    savePreferences(savedPreferences.value)
    applyThemeColors(config, savedPreferences.value)
  }

  function previewDraft() {
    applyPreferences(draftPreferences.value)
  }

  function updateDraft(next: Partial<ChatifyThemePreferences>) {
    draftPreferences.value = {
      ...draftPreferences.value,
      ...next,
      wallpaper: next.wallpaper
        ? { ...draftPreferences.value.wallpaper, ...next.wallpaper }
        : draftPreferences.value.wallpaper,
    }
    previewDraft()
  }

  function setTheme(themeId: string) {
    const theme = getThemeById(themeId)
    updateDraft({ themeId, accentColor: theme.defaultAccent })
  }

  function setAccentColor(accentColor: string) {
    updateDraft({ accentColor })
  }

  function setFontFamily(fontFamily: string) {
    updateDraft({ fontFamily })
  }

  function setWallpaper(next: Partial<WallpaperPreferences>) {
    updateDraft({
      wallpaper: {
        ...draftPreferences.value.wallpaper,
        ...next,
      },
    })
  }

  function selectWallpaperImage() {
    const imageUrl = pendingWallpaperPreview.value
      ?? draftPreferences.value.wallpaper.imageUrl
      ?? savedPreferences.value.wallpaper.imageUrl

    if (!imageUrl) {
      return false
    }

    setWallpaper({ kind: 'image', imageUrl })
    return true
  }

  function clearWallpaperImage() {
    pendingWallpaperFile.value = null
    if (pendingWallpaperPreview.value) {
      URL.revokeObjectURL(pendingWallpaperPreview.value)
      pendingWallpaperPreview.value = null
    }

    updateDraft({
      wallpaper: {
        ...draftPreferences.value.wallpaper,
        imageUrl: null,
        kind: draftPreferences.value.wallpaper.kind === 'image' ? 'none' : draftPreferences.value.wallpaper.kind,
      },
    })
  }

  function restoreDraft(preferences: ChatifyThemePreferences) {
    draftPreferences.value = clonePreferences(preferences)
    pendingWallpaperFile.value = null
    if (pendingWallpaperPreview.value) {
      URL.revokeObjectURL(pendingWallpaperPreview.value)
      pendingWallpaperPreview.value = null
    }
    previewDraft()
  }

  async function uploadAvatar(file: File): Promise<boolean> {
    if (!api.value || !boot.value?.user || avatarUploading.value) {
      return false
    }

    const toastStore = useToastStore()
    const previousAvatar = boot.value.user.attributes.avatar

    revokeActiveAvatarPreview()
    activeAvatarPreviewUrl = URL.createObjectURL(file)
    setUserAvatar(activeAvatarPreviewUrl)
    avatarUploadProgress.value = 0
    avatarRemoving.value = false

    try {
      const form = new FormData()
      form.append('avatar', file)
      const { data } = await api.value.updateSettings(form, {
        onUploadProgress: (event) => {
          if (!event.total) {
            return
          }

          avatarUploadProgress.value = Math.min(100, Math.round((event.loaded / event.total) * 100))
        },
      })

      const url = data.data.attributes.avatar_url
      if (url) {
        const nextAvatar = cacheBustUrl(url)
        setUserAvatar(nextAvatar)
        savedAvatarUrl.value = nextAvatar
      }

      revokeActiveAvatarPreview()
      return true
    } catch (err) {
      setUserAvatar(previousAvatar)
      revokeActiveAvatarPreview()
      toastStore.show({
        message: extractErrorMessage(err, 'Failed to upload avatar'),
        icon: 'error',
        placement: 'bottom',
      })
      return false
    } finally {
      avatarUploadProgress.value = null
      avatarRemoving.value = false
    }
  }

  async function removeAvatar(): Promise<boolean> {
    if (!api.value || !boot.value?.user || avatarUploading.value) {
      return false
    }

    const toastStore = useToastStore()
    const previousAvatar = boot.value.user.attributes.avatar

    avatarRemoving.value = true
    avatarUploadProgress.value = null
    revokeActiveAvatarPreview()

    try {
      const { data } = await api.value.patchSettings({ reset_avatar: true })
      const url = data.data.attributes.avatar_url
      const nextAvatar = url ? cacheBustUrl(url) : ''
      setUserAvatar(nextAvatar)
      savedAvatarUrl.value = nextAvatar || null
      return true
    } catch (err) {
      setUserAvatar(previousAvatar)
      toastStore.show({
        message: extractErrorMessage(err, 'Failed to remove avatar'),
        icon: 'error',
        placement: 'bottom',
      })
      return false
    } finally {
      avatarRemoving.value = false
      avatarUploadProgress.value = null
    }
  }

  function stageWallpaper(file: File) {
    pendingWallpaperFile.value = file
    if (pendingWallpaperPreview.value) {
      URL.revokeObjectURL(pendingWallpaperPreview.value)
    }
    const previewUrl = URL.createObjectURL(file)
    pendingWallpaperPreview.value = previewUrl
    setWallpaper({ kind: 'image', imageUrl: previewUrl })
  }

  function resetDraft() {
    draftPreferences.value = clonePreferences(savedPreferences.value)
    pendingWallpaperFile.value = null
    if (pendingWallpaperPreview.value) {
      URL.revokeObjectURL(pendingWallpaperPreview.value)
      pendingWallpaperPreview.value = null
    }
    applyPreferences(savedPreferences.value)
  }

  function commitSaved(preferences: ChatifyThemePreferences) {
    savedPreferences.value = clonePreferences(preferences)
    draftPreferences.value = clonePreferences(preferences)
    savePreferences(preferences)
    applyPreferences(preferences)
    pendingWallpaperFile.value = null
    savedAvatarUrl.value = boot.value?.user?.attributes.avatar ?? savedAvatarUrl.value
    if (pendingWallpaperPreview.value) {
      URL.revokeObjectURL(pendingWallpaperPreview.value)
      pendingWallpaperPreview.value = null
    }
  }

  function beginDraftSession() {
    draftPreferences.value = clonePreferences(savedPreferences.value)
    savedAvatarUrl.value = boot.value?.user?.attributes.avatar ?? null
    pendingWallpaperFile.value = null
    if (pendingWallpaperPreview.value) {
      URL.revokeObjectURL(pendingWallpaperPreview.value)
      pendingWallpaperPreview.value = null
    }
    previewDraft()
  }

  return {
    boot,
    api,
    savedPreferences,
    draftPreferences,
    preferences,
    isDirty,
    pendingWallpaperFile,
    pendingWallpaperPreview,
    savedAvatarUrl,
    avatarUploadProgress,
    avatarUploading,
    avatarRemoving,
    user,
    groupsEnabled,
    colors,
    features,
    colorsEnabled,
    themesEnabled,
    fontsEnabled,
    wallpaperEnabled,
    giphyEnabled,
    themePresets: themePresetsList,
    fontOptions: fontOptionsList,
    wallpaperPatterns: wallpaperPatternsList,
    attachments,
    debug,
    broadcastEnabled,
    init,
    updateDraft,
    setTheme,
    setAccentColor,
    setFontFamily,
    setWallpaper,
    selectWallpaperImage,
    clearWallpaperImage,
    uploadAvatar,
    removeAvatar,
    stageWallpaper,
    resetDraft,
    restoreDraft,
    commitSaved,
    beginDraftSession,
    previewDraft,
  }
})
