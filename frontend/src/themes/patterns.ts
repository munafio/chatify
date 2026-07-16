import type { PatternId } from './types'
import bubblesUrl from '../assets/wallpapers/bubbles.svg?url'
import circuitBoardUrl from '../assets/wallpapers/circuit-board.svg?url'
import glamorousUrl from '../assets/wallpapers/glamorous.svg?url'
import hideoutUrl from '../assets/wallpapers/hideout.svg?url'

export interface ChatPattern {
  id: PatternId
  name: string
  url: string
  tileSize: string
}

export const CHAT_PATTERNS: ChatPattern[] = [
  { id: 'bubbles', name: 'Bubbles', url: bubblesUrl, tileSize: '100px' },
  { id: 'circuit-board', name: 'Circuit', url: circuitBoardUrl, tileSize: '180px' },
  { id: 'glamorous', name: 'Glamorous', url: glamorousUrl, tileSize: '120px' },
  { id: 'hideout', name: 'Hideout', url: hideoutUrl, tileSize: '80px' },
]

export function getPatternById(id: PatternId): ChatPattern {
  return CHAT_PATTERNS.find((pattern) => pattern.id === id) ?? CHAT_PATTERNS[0]
}
