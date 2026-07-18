<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Events\UserBlockChanged;
use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class BlockService
{
    public const HIDDEN_USER_NAME = 'Unknown User';

    public function __construct(
        private readonly FavoriteService $favoriteService,
        private readonly AttachmentService $attachmentService,
    ) {}

    public function block(Model $blocker, Model $target): bool
    {
        if ((int) $blocker->getKey() === (int) $target->getKey()) {
            return false;
        }

        $existing = ChatifyModels::blockClass()::query()
            ->where('blocker_id', $blocker->getKey())
            ->where('blocked_user_id', $target->getKey())
            ->first();

        if ($existing !== null) {
            return true;
        }

        ChatifyModels::blockClass()::query()->create([
            'id' => (string) Str::uuid(),
            'blocker_id' => $blocker->getKey(),
            'blocked_user_id' => $target->getKey(),
        ]);

        if ($this->favoriteService->isFavorite($blocker, $target)) {
            $this->favoriteService->toggle($blocker, $target);
        }

        $this->broadcastBlockChanged($blocker, $target, true);

        return true;
    }

    public function unblock(Model $blocker, Model $target): bool
    {
        $deleted = ChatifyModels::blockClass()::query()
            ->where('blocker_id', $blocker->getKey())
            ->where('blocked_user_id', $target->getKey())
            ->delete();

        if ($deleted > 0) {
            $this->broadcastBlockChanged($blocker, $target, false);

            return true;
        }

        return false;
    }

    public function shouldRevealIdentity(Model $viewer, Model $target): bool
    {
        if (! $this->eitherBlocked($viewer, $target)) {
            return true;
        }

        return $this->isBlocked($viewer, $target);
    }

    public function shouldExcludeFromLists(Model $viewer, Model $target): bool
    {
        return $this->isBlocked($viewer, $target);
    }

    public function defaultAvatarUrl(): string
    {
        $folder = config('chatify.user_avatar.folder', 'users-avatar');
        $default = config('chatify.user_avatar.default', 'avatar.png');

        return $this->attachmentService->storage()->url($folder.'/'.$default);
    }

    /**
     * @return list<int>
     */
    public function blockedByMeIdsFor(Model $user): array
    {
        return ChatifyModels::blockClass()::query()
            ->where('blocker_id', $user->getKey())
            ->pluck('blocked_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function isBlocked(Model $blocker, Model $target): bool
    {
        return ChatifyModels::blockClass()::query()
            ->where('blocker_id', $blocker->getKey())
            ->where('blocked_user_id', $target->getKey())
            ->exists();
    }

    public function eitherBlocked(Model $userA, Model $userB): bool
    {
        return $this->isBlocked($userA, $userB) || $this->isBlocked($userB, $userA);
    }

    /**
     * @return list<int>
     */
    public function blockedUserIdsFor(Model $user): array
    {
        $blockedByMe = ChatifyModels::blockClass()::query()
            ->where('blocker_id', $user->getKey())
            ->pluck('blocked_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $blockedMe = ChatifyModels::blockClass()::query()
            ->where('blocked_user_id', $user->getKey())
            ->pluck('blocker_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([...$blockedByMe, ...$blockedMe]));
    }

    public function listBlockedUsers(Model $user): Collection
    {
        return ChatifyModels::blockClass()::query()
            ->where('blocker_id', $user->getKey())
            ->with('blockedUser')
            ->orderByDesc('created_at')
            ->get();
    }

    private function broadcastBlockChanged(Model $blocker, Model $target, bool $blocked): void
    {
        $blockerId = (int) $blocker->getKey();
        $targetId = (int) $target->getKey();

        UserBlockChanged::dispatch(
            $blockerId,
            $targetId,
            $blocked,
            $blockerId,
            $this->blockedUserIdsFor($blocker),
        );

        UserBlockChanged::dispatch(
            $blockerId,
            $targetId,
            $blocked,
            $targetId,
            $this->blockedUserIdsFor($target),
        );

        $this->broadcastInboxIdentityRefresh($blocker, $target);
    }

    private function broadcastInboxIdentityRefresh(Model $blocker, Model $target): void
    {
        $conversation = app(ConversationService::class)->findDirectBetween($blocker, $target);

        if ($conversation === null) {
            return;
        }

        app(InboxBroadcastService::class)->broadcastForConversation(
            $conversation->fresh([
                'participants.user',
                'messages' => fn ($query) => $query->latest()->limit(1),
            ]),
        );
    }
}
