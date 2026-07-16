<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class ForwardMessage
{
    public function __construct(
        private readonly SendMessage $sendMessage,
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Conversation $targetConversation, Message $sourceMessage, Model $sender): Message
    {
        $participant = $this->conversationService->getParticipant(
            $sourceMessage->conversation,
            (int) $sender->getKey()
        );

        if ($participant === null) {
            throw ValidationException::withMessages([
                'message' => ['You cannot forward this message.'],
            ]);
        }

        $this->conversationService->getParticipant($targetConversation, (int) $sender->getKey())
            ?? abort(403);

        $body = $sourceMessage->body;

        return $this->sendMessage->handle(
            $targetConversation,
            $sender,
            $body,
            null,
            [],
            null,
            $sourceMessage->id,
        );
    }
}
