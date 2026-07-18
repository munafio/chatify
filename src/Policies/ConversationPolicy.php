<?php

declare(strict_types=1);

namespace Chatify\Policies;

use Chatify\Models\Conversation;
use Chatify\Services\ParticipantPermissionService;
use Illuminate\Database\Eloquent\Model;

final class ConversationPolicy
{
    public function __construct(
        private readonly ParticipantPermissionService $permissionService,
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
            && $this->permissionService->hasPermission(
                $conversation,
                (int) $user->getKey(),
                ParticipantPermissionService::PERMISSION_EDIT_INFO,
            );
    }

    public function addParticipants(Model $user, Conversation $conversation): bool
    {
        return $conversation->isGroup()
            && $this->isParticipant($user, $conversation)
            && $this->permissionService->hasPermission(
                $conversation,
                (int) $user->getKey(),
                ParticipantPermissionService::PERMISSION_ADD_MEMBERS,
            );
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

        return $this->permissionService->hasPermission(
            $conversation,
            $userId,
            ParticipantPermissionService::PERMISSION_REMOVE_MEMBERS,
        );
    }

    public function manageParticipantRole(Model $user, Conversation $conversation): bool
    {
        return $conversation->isGroup()
            && $this->isParticipant($user, $conversation)
            && $this->permissionService->hasPermission(
                $conversation,
                (int) $user->getKey(),
                ParticipantPermissionService::PERMISSION_MANAGE_ADMINS,
            );
    }

    public function transferOwnership(Model $user, Conversation $conversation): bool
    {
        return $conversation->isGroup()
            && $this->permissionService->canTransferOwnership($conversation, (int) $user->getKey());
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
            return $this->permissionService->canDeleteGroup($conversation, (int) $user->getKey());
        }

        return true;
    }

    private function isParticipant(Model $user, Conversation $conversation): bool
    {
        return $this->permissionService->getParticipant($conversation, (int) $user->getKey()) !== null;
    }
}
