import axios from 'axios'

export function extractErrorMessage(error: unknown, fallback = 'Something went wrong'): string {
  if (axios.isAxiosError(error)) {
    const data = error.response?.data as {
      message?: string
      errors?: Record<string, string[]>
    } | undefined

    if (data?.errors) {
      const first = Object.values(data.errors).flat().find(Boolean)
      if (first) {
        return first
      }
    }

    if (typeof data?.message === 'string' && data.message.trim() !== '') {
      return data.message
    }
  }

  if (error instanceof Error && error.message.trim() !== '') {
    return error.message
  }

  return fallback
}
