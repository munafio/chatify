import { chatifyT } from '../i18n/nonComponent'

export type ConnectionUiState = 'online' | 'connecting' | 'updating'

export function connectionStatusLabel(state: ConnectionUiState): string | null {
  switch (state) {
    case 'connecting':
      return chatifyT('ui.connection.connecting')
    case 'updating':
      return chatifyT('ui.connection.updating')
    default:
      return null
  }
}

export function sidebarSubtitleLabel(
  state: ConnectionUiState,
  defaultLabel = chatifyT('ui.connection.messenger'),
): string {
  return connectionStatusLabel(state) ?? defaultLabel
}
