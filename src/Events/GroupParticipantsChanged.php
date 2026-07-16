<?php

declare(strict_types=1);

namespace Chatify\Events;

use Chatify\Http\Resources\UserResource;
use Chatify\Models\Conversation;
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
    ) {
        $this->conversation->loadMissing(['participants.user']);
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
        return [
            'conversation_id' => $this->conversation->id,
            'participant_count' => $this->conversation->participants->count(),
            'participants' => UserResource::collection(
                $this->conversation->participants->map->user->filter()
            )->resolve(),
        ];
    }
}
