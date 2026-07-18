<?php

declare(strict_types=1);

namespace Chatify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMembershipRevoked implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $conversationId,
        public int $userId,
        public string $reason,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'GroupMembershipRevoked';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'reason' => $this->reason,
        ];
    }
}
