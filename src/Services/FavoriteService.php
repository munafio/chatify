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
        return ChatifyModels::favoriteClass()::query()
            ->where('user_id', $user->getKey())
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
