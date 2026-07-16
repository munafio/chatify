<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Events\ConversationInboxUpdated;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Support\ChatifyModels;

final class InboxBroadcastService
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    public function broadcastForConversation(Conversation $conversation, ?Message $lastMessage = null): void
    {
        $conversation->loadMissing(['participants']);

        if ($lastMessage === null) {
            $lastMessage = ChatifyModels::messageClass()::query()
                ->forConversation($conversation->id)
                ->withSender()
                ->latest()
                ->first();
        } else {
            $lastMessage->loadMissing('sender');
        }

        foreach ($conversation->participants as $participant) {
            $userId = (int) $participant->user_id;
            $unread = $this->messageService->unreadCount($conversation, $userId);

            ConversationInboxUpdated::dispatch(
                $conversation,
                $userId,
                $lastMessage,
                $unread,
            );
        }
    }
}
