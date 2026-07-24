<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Actions\Favorites\ToggleFavorite;
use Chatify\Http\Resources\UserResource;
use Chatify\Services\FavoriteService;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FavoriteController extends Controller
{
    public function index(Request $request, FavoriteService $favoriteService): JsonResponse
    {
        $favorites = $favoriteService->listForUser($request->user());

        return UserResource::collection(
            $favorites->map->favoriteUser->filter()
        )->response();
    }

    public function toggle(Request $request, int $user, ToggleFavorite $action): JsonResponse
    {
        $target = ChatifyModels::userClass()::query()->findOrFail($user);

        if ((int) $request->user()->getKey() === (int) $target->getKey()) {
            abort(422, __('chatify::chatify.errors.cannot_favorite_self'));
        }

        $favorited = $action->handle($request->user(), $target);

        return response()->json(['data' => ['favorited' => $favorited]]);
    }
}
