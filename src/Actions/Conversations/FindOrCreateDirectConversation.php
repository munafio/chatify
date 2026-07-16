<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class FindOrCreateDirectConversation
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Model $userA, Model $userB): Conversation
    {
        return $this->conversationService->findOrCreateDirect($userA, $userB);
    }
}
