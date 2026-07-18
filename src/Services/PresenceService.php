<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Events\UserPresenceChanged;
use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class PresenceService
{
    private const CACHE_PREFIX = 'chatify:presence:';

    private const TTL_SECONDS = 45;

    public function __construct(
        private readonly UserSettingsService $userSettingsService,
    ) {}

    public function heartbeat(Model $user): bool
    {
        if (! $this->showsOnlineStatus($user)) {
            $this->markOffline($user, broadcast: true);

            return false;
        }

        $userId = (int) $user->getKey();
        $wasOnline = $this->isOnline($userId);

        Cache::put(self::CACHE_PREFIX.$userId, now()->timestamp, self::TTL_SECONDS);

        if (! $wasOnline) {
            $this->broadcastPresence($userId, true);
        }

        return true;
    }

    public function markOffline(Model $user, bool $broadcast = true): void
    {
        $userId = (int) $user->getKey();
        $wasOnline = Cache::forget(self::CACHE_PREFIX.$userId);

        if ($broadcast && $wasOnline && $this->showsOnlineStatus($user)) {
            $this->broadcastPresence($userId, false);
        }
    }

    public function isOnline(int $userId): bool
    {
        return Cache::has(self::CACHE_PREFIX.$userId);
    }

    public function showsOnlineStatus(Model $user): bool
    {
        return (bool) $this->userSettingsService->forUser($user)->active_status;
    }

    public function canViewPresence(Model $viewer, Model $target): bool
    {
        if (! $this->showsOnlineStatus($viewer) || ! $this->showsOnlineStatus($target)) {
            return false;
        }

        return true;
    }

    public function visibleIsOnline(Model $viewer, Model $target): ?bool
    {
        if (! $this->canViewPresence($viewer, $target)) {
            return null;
        }

        return $this->isOnline((int) $target->getKey());
    }

    /**
     * @return list<int>
     */
    public function audienceUserIds(int $userId): array
    {
        $participantTable = config('chatify.tables.participants', 'ch_conversation_participants');
        $conversationTable = config('chatify.tables.conversations', 'ch_conversations');
        $favoritesTable = config('chatify.tables.favorites', 'ch_favorites');

        $directPartnerIds = DB::table($participantTable.' as mine')
            ->join($participantTable.' as other', 'other.conversation_id', '=', 'mine.conversation_id')
            ->join($conversationTable, "{$conversationTable}.id", '=', 'mine.conversation_id')
            ->where('mine.user_id', $userId)
            ->where('other.user_id', '!=', $userId)
            ->where("{$conversationTable}.type", 'direct')
            ->pluck('other.user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $favoriteIds = DB::table($favoritesTable)
            ->where('user_id', $userId)
            ->pluck('favorite_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $favoritedByIds = DB::table($favoritesTable)
            ->where('favorite_user_id', $userId)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique([
            ...$directPartnerIds,
            ...$favoriteIds,
            ...$favoritedByIds,
        ]));
    }

    private function broadcastPresence(int $userId, bool $isOnline): void
    {
        foreach ($this->audienceUserIds($userId) as $audienceId) {
            UserPresenceChanged::dispatch($userId, $isOnline, $audienceId);
        }
    }
}
