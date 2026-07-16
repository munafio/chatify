<?php

declare(strict_types=1);

namespace Chatify\Policies;

use Chatify\Models\Message;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class MessagePolicy
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function view(Model $user, Message $message): bool
    {
        $message->loadMissing('conversation');

        return $this->conversationService->getParticipant(
            $message->conversation,
            (int) $user->getKey()
        ) !== null;
    }

    public function create(Model $user, Message $message): bool
    {
        $message->loadMissing('conversation');

        return $this->conversationService->getParticipant(
            $message->conversation,
            (int) $user->getKey()
        ) !== null;
    }

    public function delete(Model $user, Message $message): bool
    {
        return (int) $message->user_id === (int) $user->getKey()
            && $this->view($user, $message);
    }

    public function update(Model $user, Message $message): bool
    {
        return $this->delete($user, $message);
    }

    public function hide(Model $user, Message $message): bool
    {
        return $this->view($user, $message);
    }
}
