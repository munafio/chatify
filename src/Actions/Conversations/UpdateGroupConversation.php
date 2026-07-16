<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Models\Conversation;

final class UpdateGroupConversation
{
    public function handle(Conversation $conversation, string $name): Conversation
    {
        $conversation->forceFill(['name' => $name])->save();

        return $conversation->fresh(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);
    }
}
