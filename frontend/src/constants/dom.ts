import type { InjectionKey, Ref } from 'vue'

export const CHATIFY_TELEPORT_TARGET = '#chatify-app'

export const THREAD_ROOT_KEY: InjectionKey<Ref<HTMLElement | null>> = Symbol('chatify-thread-root')

export interface MessageScrollPin {
  isNearBottom: Ref<boolean>
  scrollToBottom: (behavior?: ScrollBehavior) => void
}

export const MESSAGE_SCROLL_PIN_KEY: InjectionKey<MessageScrollPin> = Symbol('chatify-message-scroll-pin')

export interface ConversationRouting {
  goBack: () => void
  navigateToInbox: (replace?: boolean) => void
}

export const CONVERSATION_ROUTING_KEY: InjectionKey<ConversationRouting> = Symbol('chatify-conversation-routing')
