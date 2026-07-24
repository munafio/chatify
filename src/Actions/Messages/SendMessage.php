<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Contracts\RecipientResolver;
use Chatify\Data\SendMessageData;
use Chatify\Events\MessageSent;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Services\AttachmentService;
use Chatify\Services\ConversationService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Services\MessageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class SendMessage
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly ConversationService $conversationService,
        private readonly AttachmentService $attachmentService,
        private readonly RecipientResolver $recipientResolver,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(
        Conversation $conversation,
        Model $sender,
        ?string $body,
        ?UploadedFile $attachment = null,
        array $attachments = [],
        ?string $replyToMessageId = null,
        ?string $forwardedFromMessageId = null,
        ?array $prebuiltAttachmentMeta = null,
    ): Message {
        $this->assertSenderIsParticipant($conversation, $sender);

        if ($conversation->isDirect()) {
            $recipient = $conversation->participants()
                ->where('user_id', '!=', $sender->getKey())
                ->with('user')
                ->first()?->user;

            if ($recipient !== null && ! $this->recipientResolver->canMessage($sender, $recipient)) {
                throw ValidationException::withMessages([
                    'recipient' => [__('chatify::chatify.errors.not_allowed_to_message')],
                ]);
            }
        }

        $uploadedFiles = $attachments !== [] ? $attachments : ($attachment !== null ? [$attachment] : []);
        $attachmentMeta = $prebuiltAttachmentMeta;

        if ($attachmentMeta === null && $uploadedFiles !== []) {
            $attachmentMeta = $this->attachmentService->storeMessageAttachments($uploadedFiles);
        }

        if (($body === null || trim($body) === '') && $attachmentMeta === null) {
            throw ValidationException::withMessages([
                'body' => [__('chatify::chatify.errors.body_or_attachment_required')],
            ]);
        }

        if ($replyToMessageId !== null) {
            $replyExists = Message::query()
                ->forConversation($conversation->id)
                ->where('id', $replyToMessageId)
                ->exists();

            if (! $replyExists) {
                throw ValidationException::withMessages([
                    'reply_to_message_id' => [__('chatify::chatify.errors.reply_target_not_found')],
                ]);
            }
        }

        $message = $this->messageService->send(
            $conversation,
            $sender,
            new SendMessageData(
                body: $body !== null ? trim($body) : null,
                attachmentMeta: $attachmentMeta,
                replyToMessageId: $replyToMessageId,
                forwardedFromMessageId: $forwardedFromMessageId,
            )
        );

        $this->conversationService->unhideForAllParticipants($conversation);

        MessageSent::dispatch($message);
        $this->inboxBroadcastService->broadcastForConversation($conversation->fresh(['participants']), $message);

        return $message;
    }

    private function assertSenderIsParticipant(Conversation $conversation, Model $sender): void
    {
        $participant = $this->conversationService->getParticipant($conversation, (int) $sender->getKey());

        if ($participant === null) {
            throw ValidationException::withMessages([
                'conversation' => [__('chatify::chatify.errors.not_participant')],
            ]);
        }
    }
}
