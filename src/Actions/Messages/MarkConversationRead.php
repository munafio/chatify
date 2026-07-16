<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Events\ConversationRead;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class MarkConversationRead
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Conversation $conversation, Model $user): void
    {
        $participant = $this->conversationService->getParticipant($conversation, (int) $user->getKey());

        if ($participant === null) {
            throw ValidationException::withMessages([
                'conversation' => ['You are not a participant in this conversation.'],
            ]);
        }

        $participant->markRead();

        ConversationRead::dispatch($conversation, $user);
    }
}
