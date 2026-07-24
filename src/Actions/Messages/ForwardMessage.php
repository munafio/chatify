<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Services\AttachmentService;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class ForwardMessage
{
    public function __construct(
        private readonly SendMessage $sendMessage,
        private readonly ConversationService $conversationService,
        private readonly AttachmentService $attachmentService,
    ) {}

    public function handle(Conversation $targetConversation, Message $sourceMessage, Model $sender): Message
    {
        $sourceMessage->loadMissing(['sender']);

        $participant = $this->conversationService->getParticipant(
            $sourceMessage->conversation,
            (int) $sender->getKey()
        );

        if ($participant === null) {
            throw ValidationException::withMessages([
                'message' => [__('chatify::chatify.errors.cannot_forward_message')],
            ]);
        }

        $this->conversationService->getParticipant($targetConversation, (int) $sender->getKey())
            ?? abort(403);

        if ($sourceMessage->kind === 'system') {
            throw ValidationException::withMessages([
                'message' => [__('chatify::chatify.errors.system_messages_cannot_be_forwarded')],
            ]);
        }

        $body = $sourceMessage->body;
        $attachmentMeta = $this->attachmentService->copyMessageAttachment($sourceMessage->attachment);

        return $this->sendMessage->handle(
            $targetConversation,
            $sender,
            $body,
            null,
            [],
            null,
            $sourceMessage->id,
            $attachmentMeta,
        );
    }
}
