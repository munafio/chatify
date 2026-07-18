<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class HideConversationForUser
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Conversation $conversation, Model $user): void
    {
        $participant = $this->conversationService->getParticipant($conversation, (int) $user->getKey());

        if ($participant === null) {
            abort(403);
        }

        $this->conversationService->hideForUser($conversation, (int) $user->getKey());
    }
}
