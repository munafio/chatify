<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\ConversationParticipant;
use Chatify\Support\ChatifyModels;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ConversationService
{
    public function __construct(
        private readonly BlockService $blockService,
    ) {}

    public function findOrCreateDirect(Model $userA, Model $userB): Conversation
    {
        $existing = $this->findDirectBetween($userA, $userB);

        if ($existing !== null) {
            $this->unhideForUser($existing, (int) $userA->getKey());

            return $existing;
        }

        return DB::transaction(function () use ($userA, $userB): Conversation {
            $again = $this->findDirectBetween($userA, $userB);

            if ($again !== null) {
                return $again;
            }

            $conversation = ChatifyModels::conversationClass()::query()->create([
                'id' => (string) Str::uuid(),
                'type' => Conversation::TYPE_DIRECT,
                'name' => null,
                'created_by' => $userA->getKey(),
            ]);

            foreach ([$userA, $userB] as $user) {
                ChatifyModels::participantClass()::query()->create([
                    'id' => (string) Str::uuid(),
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->getKey(),
                    'role' => ConversationParticipant::ROLE_MEMBER,
                ]);
            }

            return $conversation->load('participants');
        });
    }

    public function createGroup(Model $owner, string $name, array $userIds): Conversation
    {
        $ownerId = (int) $owner->getKey();
        $uniqueUserIds = array_values(array_unique(array_map('intval', $userIds)));
        $uniqueUserIds = array_values(array_filter(
            $uniqueUserIds,
            fn (int $id): bool => $id !== $ownerId
        ));

        $allParticipantIds = array_merge([$ownerId], $uniqueUserIds);
        $this->assertParticipantLimits(count($allParticipantIds));

        return DB::transaction(function () use ($ownerId, $name, $allParticipantIds): Conversation {
            $conversation = ChatifyModels::conversationClass()::query()->create([
                'id' => (string) Str::uuid(),
                'type' => Conversation::TYPE_GROUP,
                'name' => $name,
                'created_by' => $ownerId,
            ]);

            foreach ($allParticipantIds as $userId) {
                ChatifyModels::participantClass()::query()->create([
                    'id' => (string) Str::uuid(),
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'role' => $userId === $ownerId
                        ? ConversationParticipant::ROLE_OWNER
                        : ConversationParticipant::ROLE_MEMBER,
                ]);
            }

            return $conversation->load('participants');
        });
    }

    public function addParticipants(Conversation $conversation, array $userIds): Conversation
    {
        $this->assertIsGroup($conversation);

        $existingIds = $conversation->participants()->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $newIds = array_values(array_diff(
            array_unique(array_map('intval', $userIds)),
            $existingIds
        ));

        if ($newIds === []) {
            return $conversation->load('participants.user');
        }

        $this->assertParticipantLimits(count($existingIds) + count($newIds));

        foreach ($newIds as $userId) {
            ChatifyModels::participantClass()::query()->create([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'role' => ConversationParticipant::ROLE_MEMBER,
            ]);
        }

        return $conversation->fresh(['participants.user']);
    }

    public function removeParticipant(Conversation $conversation, int $userId): void
    {
        $this->assertIsGroup($conversation);

        $participant = $this->getParticipant($conversation, $userId);

        if ($participant === null) {
            return;
        }

        if ($participant->role === ConversationParticipant::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'user_id' => [__('chatify::chatify.errors.cannot_remove_group_owner')],
            ]);
        }

        $participant->delete();
    }

    public function isOwner(Conversation $conversation, int $userId): bool
    {
        $participant = $this->getParticipant($conversation, $userId);

        return $participant !== null && $participant->role === ConversationParticipant::ROLE_OWNER;
    }

    public function transferOwnership(Conversation $conversation, Model $from, Model $to): Conversation
    {
        $this->assertIsGroup($conversation);

        $fromId = (int) $from->getKey();
        $toId = (int) $to->getKey();

        if ($fromId === $toId) {
            throw ValidationException::withMessages([
                'user_id' => [__('chatify::chatify.errors.cannot_transfer_ownership_to_self')],
            ]);
        }

        if (! $this->isOwner($conversation, $fromId)) {
            throw ValidationException::withMessages([
                'conversation' => [__('chatify::chatify.errors.only_owner_can_transfer_ownership')],
            ]);
        }

        $targetParticipant = $this->getParticipant($conversation, $toId);

        if ($targetParticipant === null) {
            throw ValidationException::withMessages([
                'user_id' => [__('chatify::chatify.errors.target_must_be_group_member')],
            ]);
        }

        return DB::transaction(function () use ($conversation, $fromId, $toId, $targetParticipant): Conversation {
            $ownerParticipant = $this->getParticipant($conversation, $fromId);

            $targetParticipant->forceFill([
                'role' => ConversationParticipant::ROLE_OWNER,
                'permissions' => null,
            ])->save();

            $ownerParticipant?->forceFill([
                'role' => ConversationParticipant::ROLE_ADMIN,
                'permissions' => null,
            ])->save();

            return $conversation->fresh(['participants.user', 'creator', 'messages' => fn ($q) => $q->latest()->limit(1)]);
        });
    }

    public function updateParticipantRole(
        Conversation $conversation,
        int $targetUserId,
        string $role,
        ?array $permissions,
    ): ConversationParticipant {
        $this->assertIsGroup($conversation);

        $participant = $this->getParticipant($conversation, $targetUserId);

        if ($participant === null) {
            throw ValidationException::withMessages([
                'user_id' => [__('chatify::chatify.errors.user_not_group_member')],
            ]);
        }

        if ($participant->role === ConversationParticipant::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => [__('chatify::chatify.errors.cannot_change_owner_role')],
            ]);
        }

        if (! in_array($role, [
            ConversationParticipant::ROLE_ADMIN,
            ConversationParticipant::ROLE_MODERATOR,
            ConversationParticipant::ROLE_MEMBER,
        ], true)) {
            throw ValidationException::withMessages([
                'role' => [__('chatify::chatify.errors.invalid_role')],
            ]);
        }

        $participant->forceFill([
            'role' => $role,
            'permissions' => $role === ConversationParticipant::ROLE_ADMIN ? $permissions : null,
        ])->save();

        return $participant->fresh('user');
    }

    public function paginateParticipants(
        Conversation $conversation,
        int $perPage = 20,
        ?string $search = null,
        ?Model $viewer = null,
    ): LengthAwarePaginator {
        $userModel = ChatifyModels::userClass();
        $userTable = (new $userModel)->getTable();
        $participantTable = config('chatify.tables.participants', 'ch_conversation_participants');

        $query = $conversation->participants()
            ->with('user')
            ->join($userTable, "{$userTable}.id", '=', "{$participantTable}.user_id")
            ->select("{$participantTable}.*")
            ->orderByRaw("CASE {$participantTable}.role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END")
            ->orderBy("{$userTable}.name");

        if ($viewer !== null) {
            $blockedByMe = $this->blockService->blockedByMeIdsFor($viewer);

            if ($blockedByMe !== []) {
                $query->whereNotIn("{$participantTable}.user_id", $blockedByMe);
            }
        }

        if ($search !== null && $search !== '') {
            $query->where("{$userTable}.name", 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage);
    }

    public function participantsPreview(
        Conversation $conversation,
        ?int $limit = null,
        ?Model $viewer = null,
    ): \Illuminate\Support\Collection {
        $limit ??= (int) config('chatify.groups.preview_members', 6);

        $query = $conversation->participants()
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END");

        if ($viewer !== null) {
            $blockedByMe = $this->blockService->blockedByMeIdsFor($viewer);

            if ($blockedByMe !== []) {
                $query->whereNotIn('user_id', $blockedByMe);
            }
        }

        return $query->limit($limit)->get();
    }

    public function leaveGroup(Conversation $conversation, Model $user): void
    {
        $this->assertIsGroup($conversation);

        $userId = (int) $user->getKey();

        if ($this->isOwner($conversation, $userId)) {
            throw ValidationException::withMessages([
                'conversation' => [__('chatify::chatify.errors.group_owner_cannot_leave')],
            ]);
        }

        $this->removeParticipant($conversation, $userId);
    }

    public function findSavedForUser(Model $user): ?Conversation
    {
        if (! config('chatify.saved_messages.enabled', true)) {
            return null;
        }

        $userId = (int) $user->getKey();

        return ChatifyModels::conversationClass()::query()
            ->saved()
            ->where('created_by', $userId)
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $userId))
            ->first();
    }

    public function findOrCreateSavedForUser(Model $user): Conversation
    {
        $existing = $this->findSavedForUser($user);

        if ($existing !== null) {
            $this->unhideForUser($existing, (int) $user->getKey());

            return $existing;
        }

        return DB::transaction(function () use ($user): Conversation {
            $again = $this->findSavedForUser($user);

            if ($again !== null) {
                return $again;
            }

            $conversation = ChatifyModels::conversationClass()::query()->create([
                'id' => (string) Str::uuid(),
                'type' => Conversation::TYPE_SAVED,
                'name' => config('chatify.saved_messages.title', __('chatify::chatify.ui.saved_messages')),
                'created_by' => $user->getKey(),
            ]);

            ChatifyModels::participantClass()::query()->create([
                'id' => (string) Str::uuid(),
                'conversation_id' => $conversation->id,
                'user_id' => $user->getKey(),
                'role' => ConversationParticipant::ROLE_MEMBER,
            ]);

            return $conversation->load('participants');
        });
    }

    public function findDirectBetween(Model $userA, Model $userB): ?Conversation
    {
        $ids = [(int) $userA->getKey(), (int) $userB->getKey()];

        return ChatifyModels::conversationClass()::query()
            ->direct()
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $ids[0]))
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $ids[1]))
            ->has('participants', '=', 2)
            ->first();
    }

    public function forUser(Model $user): Builder
    {
        return ChatifyModels::conversationClass()::query()
            ->forUser((int) $user->getKey())
            ->with(['participants.user', 'messages' => fn ($q) => $q->latest()->limit(1)]);
    }

    public function getParticipant(Conversation $conversation, int $userId): ?ConversationParticipant
    {
        return $conversation->participants()->where('user_id', $userId)->first();
    }

    public function delete(Conversation $conversation): void
    {
        $conversation->delete();
    }

    public function hideForUser(Conversation $conversation, int $userId): void
    {
        $participant = $this->getParticipant($conversation, $userId);

        if ($participant === null) {
            abort(403);
        }

        $participant->forceFill(['hidden_at' => now()])->save();
    }

    public function unhideForUser(Conversation $conversation, int $userId): void
    {
        $participant = $this->getParticipant($conversation, $userId);

        if ($participant === null || $participant->hidden_at === null) {
            return;
        }

        $participant->forceFill(['hidden_at' => null])->save();
    }

    public function unhideForAllParticipants(Conversation $conversation): void
    {
        $conversation->participants()
            ->whereNotNull('hidden_at')
            ->update(['hidden_at' => null]);
    }

    private function assertIsGroup(Conversation $conversation): void
    {
        if (! $conversation->isGroup()) {
            throw ValidationException::withMessages([
                'conversation' => [__('chatify::chatify.errors.group_only_action')],
            ]);
        }
    }

    private function assertParticipantLimits(int $count): void
    {
        $min = (int) config('chatify.groups.min_participants', 2);
        $max = (int) config('chatify.groups.max_participants', 50);

        if ($count < $min) {
            throw ValidationException::withMessages([
                'user_ids' => [__('chatify::chatify.errors.group_min_participants', ['min' => $min])],
            ]);
        }

        if ($count > $max) {
            throw ValidationException::withMessages([
                'user_ids' => [__('chatify::chatify.errors.group_max_participants', ['max' => $max])],
            ]);
        }
    }
}
