<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class FavoriteService
{
    public function toggle(Model $user, Model $target): bool
    {
        if ($user->getKey() === $target->getKey()) {
            return false;
        }

        $favorite = ChatifyModels::favoriteClass()::query()
            ->where('user_id', $user->getKey())
            ->where('favorite_user_id', $target->getKey())
            ->first();

        if ($favorite !== null) {
            $favorite->delete();

            return false;
        }

        ChatifyModels::favoriteClass()::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->getKey(),
            'favorite_user_id' => $target->getKey(),
        ]);

        return true;
    }

    public function listForUser(Model $user): Collection
    {
        $blockedByMe = ChatifyModels::blockClass()::query()
            ->where('blocker_id', $user->getKey())
            ->pluck('blocked_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return ChatifyModels::favoriteClass()::query()
            ->where('user_id', $user->getKey())
            ->when($blockedByMe !== [], fn ($query) => $query->whereNotIn('favorite_user_id', $blockedByMe))
            ->with('favoriteUser')
            ->get();
    }

    public function isFavorite(Model $user, Model $target): bool
    {
        return ChatifyModels::favoriteClass()::query()
            ->where('user_id', $user->getKey())
            ->where('favorite_user_id', $target->getKey())
            ->exists();
    }
}
