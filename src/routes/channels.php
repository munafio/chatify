<?php

declare(strict_types=1);

use Chatify\Support\ChatifyModels;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chatify.conversation.{conversationId}', function ($user, string $conversationId) {
    return ChatifyModels::participantClass()::query()
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->getKey())
        ->exists();
});

Broadcast::channel('chatify.user.{userId}', function ($user, int|string $userId) {
    return (int) $user->getKey() === (int) $userId;
});
