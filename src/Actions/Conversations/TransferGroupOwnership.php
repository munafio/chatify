<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupParticipantsChanged;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Services\ParticipantPermissionService;
use Illuminate\Database\Eloquent\Model;

final class TransferGroupOwnership
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Conversation $conversation, Model $from, Model $to): Conversation
    {
        $conversation = $this->conversationService->transferOwnership($conversation, $from, $to);

        GroupParticipantsChanged::dispatch($conversation);

        return $conversation;
    }
}
