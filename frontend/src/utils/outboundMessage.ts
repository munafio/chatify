import type { ChatifyMessage, ChatifyUser, MessageAttachment, MessageReplyPreview } from '../types'

export type MessageLocalStatus = 'sending' | 'failed'

export interface OutboundMessageDraft {
  conversationId: string
  body: string
  attachment?: File
  attachments?: File[]
  reply_to_message_id?: string
  reply_to?: MessageReplyPreview | null
  blobUrls: string[]
}

export function isPendingMessageId(id: string): boolean {
  return id.startsWith('pending-')
}

function fileToAttachment(file: File, url: string): MessageAttachment {
  return {
    filename: file.name,
    original_name: file.name,
    type: file.type.startsWith('image/') ? 'image' : 'file',
    url,
  }
}

export function buildOptimisticMessage(
  tempId: string,
  draft: Omit<OutboundMessageDraft, 'blobUrls'>,
  user: ChatifyUser,
): { message: ChatifyMessage; blobUrls: string[] } {
  const blobUrls: string[] = []
  const now = new Date().toISOString()
  let attachment: MessageAttachment | null = null
  let attachments: MessageAttachment[] | undefined

  if (draft.attachments?.length) {
    attachments = draft.attachments.map((file) => {
      const url = URL.createObjectURL(file)
      blobUrls.push(url)
      return fileToAttachment(file, url)
    })
  } else if (draft.attachment) {
    const url = URL.createObjectURL(draft.attachment)
    blobUrls.push(url)
    attachment = fileToAttachment(draft.attachment, url)
  }

  const message: ChatifyMessage = {
    type: 'message',
    id: tempId,
    attributes: {
      conversation_id: draft.conversationId,
      body: draft.body || null,
      attachment,
      attachments,
      read: false,
      reply_to: draft.reply_to ?? null,
      local_status: 'sending',
      created_at: now,
      updated_at: now,
    },
    relationships: {
      sender: {
        data: {
          type: 'user',
          id: user.id,
        },
      },
    },
  }

  return { message, blobUrls }
}

export function revokeBlobUrls(urls: string[]) {
  urls.forEach((url) => URL.revokeObjectURL(url))
}
