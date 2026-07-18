<?php

declare(strict_types=1);

namespace Chatify\Actions\Conversations;

use Chatify\Events\GroupParticipantsChanged;
use Chatify\Models\Conversation;
use Chatify\Models\ConversationParticipant;
use Chatify\Services\ConversationService;
use Chatify\Services\ParticipantPermissionService;

final class UpdateGroupParticipantRole
{
    public function __construct(
        private readonly ConversationService $conversationService,
        private readonly ParticipantPermissionService $permissionService,
    ) {}

    public function handle(
        Conversation $conversation,
        int $targetUserId,
        string $role,
        ?array $permissions,
        int $actorId,
    ): Conversation {
        $allowFullAdmin = $this->permissionService->canPromoteToFullAdmin($conversation, $actorId);

        if ($role === ConversationParticipant::ROLE_ADMIN) {
            $this->permissionService->validateAdminPermissions($permissions, $allowFullAdmin, $conversation, $actorId);
        }

        $this->conversationService->updateParticipantRole($conversation, $targetUserId, $role, $permissions);

        $conversation = $conversation->fresh(['participants.user', 'creator', 'messages' => fn ($q) => $q->latest()->limit(1)]);

        GroupParticipantsChanged::dispatch($conversation);

        return $conversation;
    }
}
