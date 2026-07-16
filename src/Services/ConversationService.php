<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\ConversationParticipant;
use Chatify\Support\ChatifyModels;
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

            /** @var Conversation $conversation */
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
            /** @var Conversation $conversation */
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
                ]);
            }

            return $conversation->load('participants');
        });
    }

    /**
     * @param  list<int>  $userIds
     */
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

        $participant->delete();
    }

    public function isOwner(Conversation $conversation, int $userId): bool
    {
        return (int) $conversation->created_by === $userId;
    }

    public function leaveGroup(Conversation $conversation, Model $user): void
    {
        $this->assertIsGroup($conversation);

        $userId = (int) $user->getKey();

        if ($this->isOwner($conversation, $userId)) {
            throw ValidationException::withMessages([
                'conversation' => ['Group owner cannot leave. Delete the group instead.'],
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
