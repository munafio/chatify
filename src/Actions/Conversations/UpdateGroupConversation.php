<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;

final class UpdateGroupConversation
{
    public function handle(Conversation $conversation, ?string $name = null, ?string $description = null): Conversation
    {
        $updates = [];

        if ($name !== null) {
            $updates['name'] = $name;
        }

        if ($description !== null) {
            $updates['description'] = $description;
        }

        if ($updates !== []) {
            $conversation->forceFill($updates)->save();
        }

        return $conversation->fresh(['participants.user', 'creator', 'messages' => fn ($q) => $q->latest()->limit(1)]);
    }
}
