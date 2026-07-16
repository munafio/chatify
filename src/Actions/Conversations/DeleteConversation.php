<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;
use Chatify\Services\AttachmentService;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class DeleteConversation
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly AttachmentService $attachmentService,
    ) {}

    public function handle(Conversation $conversation, Model $user): void
    {
        $participant = $this->conversationService->getParticipant($conversation, (int) $user->getKey());

        if ($participant === null) {
            abort(403);
        }

        $this->attachmentService->deleteConversationAttachments($conversation);
        $this->conversationService->delete($conversation);
    }
}
