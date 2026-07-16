<?php

declare(strict_types=1);

namespace Chatify\Events;

use Chatify\Http\Resources\ConversationResource;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Support\ChatifyModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\SerializesModels;

class ConversationInboxUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $userId,
        public ?Message $lastMessage,
        public int $unreadCount,
    ) {
        $this->conversation->loadMissing(['participants.user']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chatify.user.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'InboxUpdated';
    }

    public function broadcastWith(): array
    {
        $user = ChatifyModels::userClass()::query()->find($this->userId);

        if ($user === null) {
            return [];
        }

        $conversation = $this->conversation;
        $conversation->setRelation(
            'messages',
            $this->lastMessage !== null ? collect([$this->lastMessage->loadMissing('sender')]) : collect()
        );

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        return (new ConversationResource($conversation))->toArray($request);
    }
}
