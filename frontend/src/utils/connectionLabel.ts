export type ConnectionUiState = 'online' | 'connecting' | 'updating'

export function connectionStatusLabel(state: ConnectionUiState): string | null {
  switch (state) {
    case 'connecting':
      return 'Connecting…'
    case 'updating':
      return 'Updating…'
    default:
      return null
  }
}

export function sidebarSubtitleLabel(state: ConnectionUiState, defaultLabel = 'Messenger'): string {
  return connectionStatusLabel(state) ?? defaultLabel
}
