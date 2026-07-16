/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<object, object, unknown>
  export default component
}

declare module 'laravel-echo' {
  import type { default as Pusher } from 'pusher-js'

  export default class Echo<T extends string = 'pusher'> {
    constructor(options: Record<string, unknown>)
    private(channel: string): {
      listen(event: string, callback: (payload: unknown) => void): unknown
    }
    leave(channel: string): void
    disconnect(): void
  }
}
