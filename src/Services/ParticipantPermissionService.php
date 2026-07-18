<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\ConversationParticipant;

final class ParticipantPermissionService
{
    public const PERMISSION_EDIT_INFO = 'edit_info';

    public const PERMISSION_ADD_MEMBERS = 'add_members';

    public const PERMISSION_REMOVE_MEMBERS = 'remove_members';

    public const PERMISSION_MANAGE_ADMINS = 'manage_admins';

    public const ALL_PERMISSIONS = [
        self::PERMISSION_EDIT_INFO,
        self::PERMISSION_ADD_MEMBERS,
        self::PERMISSION_REMOVE_MEMBERS,
        self::PERMISSION_MANAGE_ADMINS,
    ];

    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function isOwner(Conversation $conversation, int $userId): bool
    {
        return $this->conversationService->isOwner($conversation, $userId);
    }

    public function getParticipant(Conversation $conversation, int $userId): ?ConversationParticipant
    {
        return $this->conversationService->getParticipant($conversation, $userId);
    }

    public function membership(Conversation $conversation, int $userId): ?array
    {
        $participant = $this->getParticipant($conversation, $userId);

        if ($participant === null) {
            return null;
        }

        $permissions = $participant->permissions;

        return [
            'role' => $participant->role,
            'permissions' => is_array($permissions) ? $permissions : null,
            'is_full_admin' => $participant->role === ConversationParticipant::ROLE_ADMIN
                && $participant->permissions === null,
        ];
    }

    public function hasPermission(Conversation $conversation, int $userId, string $permission): bool
    {
        $participant = $this->getParticipant($conversation, $userId);

        if ($participant === null) {
            return false;
        }

        return $participant->hasPermission($permission);
    }

    public function canDeleteGroup(Conversation $conversation, int $userId): bool
    {
        return $this->isOwner($conversation, $userId);
    }

    public function canTransferOwnership(Conversation $conversation, int $userId): bool
    {
        return $this->isOwner($conversation, $userId);
    }

    public function canPromoteToFullAdmin(Conversation $conversation, int $userId): bool
    {
        return $this->isOwner($conversation, $userId);
    }

    public function validateAdminPermissions(?array $permissions, bool $allowFullAdmin, Conversation $conversation, int $actorId): void
    {
        if ($permissions === null) {
            if (! $allowFullAdmin || ! $this->canPromoteToFullAdmin($conversation, $actorId)) {
                abort(422, 'Only the group owner can assign full admin permissions.');
            }

            return;
        }

        foreach (array_keys($permissions) as $key) {
            if (! in_array($key, self::ALL_PERMISSIONS, true)) {
                abort(422, "Invalid permission key: {$key}");
            }
        }
    }
}
