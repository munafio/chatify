<?php

declare(strict_types=1);

namespace Chatify\Policies;

use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Illuminate\Database\Eloquent\Model;

final class ConversationPolicy
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function view(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function create(Model $user): bool
    {
        return true;
    }

    public function update(Model $user, Conversation $conversation): bool
    {
        return $this->isParticipant($user, $conversation);
    }

    public function updateGroup(Model $user, Conversation $conversation): bool
    {
        return $conversation->isGroup()
            && $this->isParticipant($user, $conversation)
            && $this->conversationService->isOwner($conversation, (int) $user->getKey());
    }

    public function addParticipants(Model $user, Conversation $conversation): bool
    {
        return $this->updateGroup($user, $conversation);
    }

    public function removeParticipant(Model $user, Conversation $conversation, int $targetUserId): bool
    {
        if (! $conversation->isGroup() || ! $this->isParticipant($user, $conversation)) {
            return false;
        }

        $userId = (int) $user->getKey();

        if ($userId === $targetUserId) {
            return true;
        }

        return $this->conversationService->isOwner($conversation, $userId);
    }

    public function leave(Model $user, Conversation $conversation): bool
    {
        return $conversation->isGroup() && $this->isParticipant($user, $conversation);
    }

    public function delete(Model $user, Conversation $conversation): bool
    {
        if (! $this->isParticipant($user, $conversation)) {
            return false;
        }

        if ($conversation->isGroup()) {
            return $this->conversationService->isOwner($conversation, (int) $user->getKey());
        }

        return true;
    }

    private function isParticipant(Model $user, Conversation $conversation): bool
    {
        return $this->conversationService->getParticipant($conversation, (int) $user->getKey()) !== null;
    }
}
