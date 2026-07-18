<?php

declare(strict_types=1);

namespace Chatify\Events;

use Chatify\Http\Resources\ParticipantResource;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupParticipantsChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public ?string $changeType = null,
        public ?int $actorUserId = null,
        public array $targetUserIds = [],
    ) {
        $this->conversation->loadMissing(['participants.user', 'creator']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'GroupParticipantsChanged';
    }

    public function broadcastWith(): array
    {
        $preview = app(ConversationService::class)->participantsPreview($this->conversation);

        return [
            'conversation_id' => $this->conversation->id,
            'change_type' => $this->changeType,
            'actor_user_id' => $this->actorUserId,
            'target_user_ids' => $this->targetUserIds,
            'participant_count' => $this->conversation->participants->count(),
            'participants_preview' => ParticipantResource::collection($preview)->resolve(),
        ];
    }
}
