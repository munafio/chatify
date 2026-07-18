<?php

declare(strict_types=1);

namespace Chatify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $userId,
        public bool $isOnline,
        public int $audienceUserId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.user.'.$this->audienceUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserPresenceChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'is_online' => $this->isOnline,
        ];
    }
}
