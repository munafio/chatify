<?php

declare(strict_types=1);

namespace Chatify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public bool $active,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chatify.presence'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserPresenceChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->getKey(),
            'active' => $this->active,
        ];
    }
}
