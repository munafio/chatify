import type { InjectionKey, Ref } from 'vue'

export const CHATIFY_TELEPORT_TARGET = '#chatify-app'

export const THREAD_ROOT_KEY: InjectionKey<Ref<HTMLElement | null>> = Symbol('chatify-thread-root')
