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
    public function findOrCreateDirect(Model $userA, Model $userB): Conversation
    {
        $existing = $this->findDirectBetween($userA, $userB);

        if ($existing !== null) {
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
                'user_id' => ['Cannot remove the group owner. Transfer ownership first.'],
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
                'user_id' => ['Cannot transfer ownership to yourself.'],
            ]);
        }

        if (! $this->isOwner($conversation, $fromId)) {
            throw ValidationException::withMessages([
                'conversation' => ['Only the group owner can transfer ownership.'],
            ]);
        }

        $targetParticipant = $this->getParticipant($conversation, $toId);

        if ($targetParticipant === null) {
            throw ValidationException::withMessages([
                'user_id' => ['Target user must be a group member.'],
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
                'user_id' => ['User is not a group member.'],
            ]);
        }

        if ($participant->role === ConversationParticipant::ROLE_OWNER) {
            throw ValidationException::withMessages([
                'role' => ['Cannot change the owner role directly. Use transfer ownership.'],
            ]);
        }

        if (! in_array($role, [
            ConversationParticipant::ROLE_ADMIN,
            ConversationParticipant::ROLE_MODERATOR,
            ConversationParticipant::ROLE_MEMBER,
        ], true)) {
            throw ValidationException::withMessages([
                'role' => ['Invalid role.'],
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

        if ($search !== null && $search !== '') {
            $query->where("{$userTable}.name", 'like', '%'.$search.'%');
        }

        return $query->paginate($perPage);
    }

    public function participantsPreview(Conversation $conversation, ?int $limit = null): \Illuminate\Support\Collection
    {
        $limit ??= (int) config('chatify.groups.preview_members', 6);

        return $conversation->participants()
            ->with('user')
            ->orderByRaw("CASE role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END")
            ->limit($limit)
            ->get();
    }

    public function leaveGroup(Conversation $conversation, Model $user): void
    {
        $this->assertIsGroup($conversation);

        $userId = (int) $user->getKey();

        if ($this->isOwner($conversation, $userId)) {
            throw ValidationException::withMessages([
                'conversation' => ['Group owner cannot leave. Transfer ownership or delete the group instead.'],
            ]);
        }

        $this->removeParticipant($conversation, $userId);
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

    private function assertIsGroup(Conversation $conversation): void
    {
        if (! $conversation->isGroup()) {
            throw ValidationException::withMessages([
                'conversation' => ['This action is only available for group conversations.'],
            ]);
        }
    }

    private function assertParticipantLimits(int $count): void
    {
        $min = (int) config('chatify.groups.min_participants', 2);
        $max = (int) config('chatify.groups.max_participants', 50);

        if ($count < $min) {
            throw ValidationException::withMessages([
                'user_ids' => ["A group must have at least {$min} participants."],
            ]);
        }

        if ($count > $max) {
            throw ValidationException::withMessages([
                'user_ids' => ["A group cannot exceed {$max} participants."],
            ]);
        }
    }
}
