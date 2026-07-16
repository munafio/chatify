<?php

declare(strict_types=1);

namespace Chatify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $conversationId,
        public int $userId,
        public bool $isTyping,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.conversation.'.$this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserTyping';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'is_typing' => $this->isTyping,
        ];
    }
}
