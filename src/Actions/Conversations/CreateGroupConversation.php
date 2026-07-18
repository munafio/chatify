<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class CreateGroupConversation
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Model $owner, string $name, array $userIds): Conversation
    {
        return $this->conversationService->createGroup($owner, $name, $userIds);
    }
}
