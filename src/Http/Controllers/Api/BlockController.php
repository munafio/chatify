<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\UserResource;
use Chatify\Services\BlockService;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BlockController extends Controller
{
    public function index(Request $request, BlockService $blockService): JsonResponse
    {
        $blocks = $blockService->listBlockedUsers($request->user());

        return UserResource::collection(
            $blocks->map->blockedUser->filter()
        )->additional([
            'meta' => [
                'messaging_blocked_user_ids' => $blockService->blockedUserIdsFor($request->user()),
            ],
        ])->response();
    }

    public function store(Request $request, int $user, BlockService $blockService): JsonResponse
    {
        $target = ChatifyModels::userClass()::query()->findOrFail($user);

        if ((int) $request->user()->getKey() === (int) $target->getKey()) {
            abort(422, 'You cannot block yourself.');
        }

        $blockService->block($request->user(), $target);

        return response()->json(['data' => ['blocked' => true]]);
    }

    public function destroy(Request $request, int $user, BlockService $blockService): JsonResponse
    {
        $target = ChatifyModels::userClass()::query()->findOrFail($user);

        $blockService->unblock($request->user(), $target);

        return response()->json(['data' => ['blocked' => false]]);
    }
}
