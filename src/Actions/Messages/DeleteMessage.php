<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Events\MessageDeleted;
use Chatify\Models\Message;
use Chatify\Services\AttachmentService;
use Chatify\Services\InboxBroadcastService;
use Chatify\Services\MessageService;
use Illuminate\Database\Eloquent\Model;

final class DeleteMessage
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly AttachmentService $attachmentService,
        private readonly InboxBroadcastService $inboxBroadcastService,
        private readonly HideMessageForUser $hideMessageForUser,
    ) {}

    public function handle(Message $message, Model $user, string $scope = 'all'): void
    {
        if ($scope === 'me') {
            $this->hideMessageForUser->handle($message, $user);

            return;
        }

        if ((int) $message->user_id !== (int) $user->getKey()) {
            abort(403);
        }

        $conversation = $message->conversation;
        $this->attachmentService->deleteForMessage($message);
        $this->messageService->delete($message);

        MessageDeleted::dispatch($message);
        $this->inboxBroadcastService->broadcastForConversation($conversation->fresh(['participants']));
    }
}
