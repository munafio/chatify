<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupParticipantsChanged;
use Chatify\Events\MessageSent;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Services\GroupSystemMessageService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Support\ChatifyModels;
use Chatify\Support\GroupSystemMessageFormatter;
use Illuminate\Database\Eloquent\Model;

final class AddGroupParticipants
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly GroupSystemMessageService $groupSystemMessageService,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(Conversation $conversation, Model $actor, array $userIds): Conversation
    {
        $existingIds = $conversation->participants()->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $newIds = array_values(array_diff(
            array_unique(array_map('intval', $userIds)),
            $existingIds
        ));

        if ($newIds === []) {
            return $conversation->load('participants.user');
        }

        $conversation = $this->conversationService->addParticipants($conversation, $userIds);
        $targets = ChatifyModels::userClass()::query()->whereIn('id', $newIds)->get();

        $message = $this->groupSystemMessageService->record(
            $conversation,
            $actor,
            'participant_added',
            $newIds,
            GroupSystemMessageFormatter::participantAdded($actor, $targets),
        );

        MessageSent::dispatch($message);
        $this->inboxBroadcastService->broadcastForConversation($conversation->fresh(['participants']), $message);
        GroupParticipantsChanged::dispatch($conversation, 'participant_added', (int) $actor->getKey(), $newIds);

        return $conversation;
    }
}
