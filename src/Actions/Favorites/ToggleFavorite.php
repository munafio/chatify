<?php

declare(strict_types=1);

namespace Chatify\Actions\Favorites;

use Chatify\Services\FavoriteService;
use Illuminate\Database\Eloquent\Model;

final class ToggleFavorite
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
    ) {}

    public function handle(Model $user, Model $target): bool
    {
        return $this->favoriteService->toggle($user, $target);
    }
}
