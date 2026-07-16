<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupParticipantsChanged;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class LeaveGroupConversation
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Conversation $conversation, Model $user): Conversation
    {
        $this->conversationService->leaveGroup($conversation, $user);

        $conversation = $conversation->fresh(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);

        GroupParticipantsChanged::dispatch($conversation);

        return $conversation;
    }
}
