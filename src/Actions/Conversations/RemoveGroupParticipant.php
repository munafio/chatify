<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupParticipantsChanged;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;

final class RemoveGroupParticipant
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Conversation $conversation, int $userId): Conversation
    {
        $this->conversationService->removeParticipant($conversation, $userId);

        $conversation = $conversation->fresh(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);

        GroupParticipantsChanged::dispatch($conversation);

        return $conversation;
    }
}
