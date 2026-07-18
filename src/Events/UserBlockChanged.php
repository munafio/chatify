<?php

declare(strict_types=1);

namespace Chatify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserBlockChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  list<int>  $messagingBlockedUserIds
     */
    public function __construct(
        public int $blockerId,
        public int $blockedUserId,
        public bool $blocked,
        public int $audienceUserId,
        public array $messagingBlockedUserIds,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.user.'.$this->audienceUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserBlockChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'blocker_id' => $this->blockerId,
            'blocked_user_id' => $this->blockedUserId,
            'blocked' => $this->blocked,
            'messaging_blocked_user_ids' => $this->messagingBlockedUserIds,
        ];
    }
}
