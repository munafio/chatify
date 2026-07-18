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
use Chatify\Support\ChatifyModels;
use Chatify\Support\GroupSystemMessageFormatter;
use Illuminate\Database\Eloquent\Model;

final class RemoveGroupParticipant
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly GroupSystemMessageService $groupSystemMessageService,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(Conversation $conversation, Model $actor, int $userId): Conversation
    {
        $target = ChatifyModels::userClass()::query()->findOrFail($userId);
        $isSelf = (int) $actor->getKey() === $userId;
        $event = $isSelf ? 'participant_left' : 'participant_removed';

        $this->conversationService->removeParticipant($conversation, $userId);

        $conversation = $conversation->fresh(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);

        $message = $this->groupSystemMessageService->record(
            $conversation,
            $isSelf ? $target : $actor,
            $event,
            [$userId],
            $isSelf
                ? GroupSystemMessageFormatter::participantLeft($target)
                : GroupSystemMessageFormatter::participantRemoved($actor, $target),
        );

        MessageSent::dispatch($message);
        $this->inboxBroadcastService->broadcastForConversation($conversation, $message);
        GroupParticipantsChanged::dispatch($conversation, $event, (int) $actor->getKey(), [$userId]);
        GroupMembershipRevoked::dispatch(
            $conversation->id,
            $userId,
            $isSelf ? 'left' : 'removed',
        );

        return $conversation;
    }
}
