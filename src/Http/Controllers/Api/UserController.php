<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Http\Resources\UserResource;
use Chatify\Support\ChatifyModels;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function show(int $user): JsonResponse
    {
        $model = ChatifyModels::userClass()::query()->findOrFail($user);

        return (new UserResource($model))->response();
    }
}
