<?php

declare(strict_types=1);

namespace Chatify\Events;

use Chatify\Http\Resources\MessageResource;
use Chatify\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Message $message,
    ) {
        $this->message->loadMissing(['sender', 'conversation.participants', 'forwardedFrom.sender']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return (new MessageResource($this->message))->resolve();
    }
}
