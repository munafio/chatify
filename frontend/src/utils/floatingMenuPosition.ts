export interface FloatingMenuPositionOptions {
  align?: 'start' | 'end'
  placement?: 'below' | 'above'
  gap?: number
  margin?: number
  isRtl?: boolean
}

export function clampFloatingMenuPosition(
  x: number,
  y: number,
  menuWidth: number,
  menuHeight: number,
  margin = 8,
): { x: number; y: number } {
  let nextX = x
  let nextY = y

  if (nextX + menuWidth > window.innerWidth - margin) {
    nextX = window.innerWidth - menuWidth - margin
  }
  if (nextX < margin) {
    nextX = margin
  }

  if (nextY + menuHeight > window.innerHeight - margin) {
    nextY = window.innerHeight - menuHeight - margin
  }
  if (nextY < margin) {
    nextY = margin
  }

  return { x: nextX, y: nextY }
}

export function floatingMenuPositionFromRect(
  rect: DOMRect,
  menuWidth: number,
  menuHeight: number,
  options: FloatingMenuPositionOptions = {},
): { x: number; y: number } {
  const {
    align = 'start',
    placement = 'below',
    gap = 4,
    margin = 8,
    isRtl = false,
  } = options

  const alignStart = align !== 'end'
  const x = alignStart
    ? (isRtl ? rect.right - menuWidth : rect.left)
    : (isRtl ? rect.left : rect.right - menuWidth)

  let y = placement === 'below' ? rect.bottom + gap : rect.top - menuHeight - gap

  if (placement === 'below' && y + menuHeight > window.innerHeight - margin) {
    y = rect.top - menuHeight - gap
  } else if (placement === 'above' && y < margin) {
    y = rect.bottom + gap
  }

  return clampFloatingMenuPosition(x, y, menuWidth, menuHeight, margin)
}

export function floatingMenuPositionFromPoint(
  x: number,
  y: number,
  menuWidth: number,
  menuHeight: number,
  margin = 8,
): { x: number; y: number } {
  let nextX = x
  let nextY = y

  if (nextX + menuWidth > window.innerWidth - margin) {
    nextX = x - menuWidth
  }
  if (nextX < margin) {
    nextX = margin
  }

  if (nextY + menuHeight > window.innerHeight - margin) {
    nextY = y - menuHeight
  }
  if (nextY < margin) {
    nextY = margin
  }

  return clampFloatingMenuPosition(nextX, nextY, menuWidth, menuHeight, margin)
}
