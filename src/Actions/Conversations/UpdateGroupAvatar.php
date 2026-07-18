<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;
use Chatify\Services\AttachmentService;
use Illuminate\Http\UploadedFile;

final class UpdateGroupAvatar
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    public function handle(Conversation $conversation, UploadedFile $file): Conversation
    {
        $stored = $this->attachmentService->storeGroupAvatar($file);

        $conversation->forceFill(['avatar' => $stored['stored_name']])->save();

        return $conversation->fresh(['participants.user', 'creator', 'messages' => fn ($q) => $q->latest()->limit(1)]);
    }
}
