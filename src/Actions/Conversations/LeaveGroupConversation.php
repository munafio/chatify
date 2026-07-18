<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupMembershipRevoked;
use Chatify\Events\GroupParticipantsChanged;
use Chatify\Events\MessageSent;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Services\GroupSystemMessageService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Support\GroupSystemMessageFormatter;
use Illuminate\Database\Eloquent\Model;

final class LeaveGroupConversation
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly GroupSystemMessageService $groupSystemMessageService,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(Conversation $conversation, Model $user): Conversation
    {
        $userId = (int) $user->getKey();

        $this->conversationService->leaveGroup($conversation, $user);

        $conversation = $conversation->fresh(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);

        $message = $this->groupSystemMessageService->record(
            $conversation,
            $user,
            'participant_left',
            [$userId],
            GroupSystemMessageFormatter::participantLeft($user),
        );

        MessageSent::dispatch($message);
        $this->inboxBroadcastService->broadcastForConversation($conversation, $message);
        GroupParticipantsChanged::dispatch($conversation, 'participant_left', $userId, [$userId]);
        GroupMembershipRevoked::dispatch($conversation->id, $userId, 'left');

        return $conversation;
    }
}
