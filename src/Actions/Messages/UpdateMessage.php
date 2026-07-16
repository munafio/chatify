<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Events\MessageUpdated;
use Chatify\Models\Message;
use Chatify\Services\InboxBroadcastService;
use Chatify\Services\MessageService;
use Illuminate\Database\Eloquent\Model;

final class UpdateMessage
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(Message $message, Model $user, string $body): Message
    {
        if ((int) $message->user_id !== (int) $user->getKey()) {
            abort(403);
        }

        $updated = $this->messageService->edit($message, $body);

        MessageUpdated::dispatch($updated);
        $this->inboxBroadcastService->broadcastForConversation($message->conversation->fresh(['participants']), $updated);

        return $updated;
    }
}
