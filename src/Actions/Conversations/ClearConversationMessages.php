<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;
use Chatify\Services\AttachmentService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Services\MessageService;
use Illuminate\Database\Eloquent\Model;

final class ClearConversationMessages
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly AttachmentService $attachmentService,
        private readonly InboxBroadcastService $inboxBroadcastService,
    ) {}

    public function handle(Conversation $conversation, Model $user): Conversation
    {
        $this->attachmentService->deleteConversationAttachments($conversation);
        $this->messageService->deleteAllForConversation($conversation);

        $conversation = $conversation->fresh(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);
        $this->inboxBroadcastService->broadcastForConversation($conversation);

        return $conversation;
    }
}
