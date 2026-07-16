<?php

declare(strict_types=1);

namespace Chatify\Events;

use Chatify\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationRead implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Model $user,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationRead';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->getKey(),
            'read_at' => now()->toIso8601String(),
        ];
    }
}
