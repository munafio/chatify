<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupParticipantsChanged;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;

final class AddGroupParticipants
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * @param  list<int>  $userIds
     */
    public function handle(Conversation $conversation, array $userIds): Conversation
    {
        $conversation = $this->conversationService->addParticipants($conversation, $userIds);

        GroupParticipantsChanged::dispatch($conversation);

        return $conversation;
    }
}
