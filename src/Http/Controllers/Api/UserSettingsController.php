<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Actions\Settings\UpdateUserSettings;
use Chatify\Http\Requests\UpdateSettingsRequest;
use Chatify\Http\Requests\UploadAvatarRequest;
use Chatify\Http\Requests\UploadChatBackgroundRequest;
use Chatify\Http\Resources\UserSettingsResource;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserSettingsController extends Controller
{
    public function show(Request $request, UserSettingsService $settingsService): JsonResponse
    {
        $settings = $settingsService->forUser($request->user());

        return (new UserSettingsResource($settings))->response();
    }

    public function update(UpdateSettingsRequest $request, UpdateUserSettings $action): JsonResponse
    {
        $settings = $action->handle(
            $request->user(),
            $request->validatedSettingsAttributes(),
            $request->file('avatar'),
        );

        return (new UserSettingsResource($settings))->response();
    }

    public function updateAvatar(UploadAvatarRequest $request, UpdateUserSettings $action): JsonResponse
    {
        $settings = $action->handle(
            $request->user(),
            [],
            $request->file('avatar'),
        );

        return (new UserSettingsResource($settings))->response();
    }

    public function updateChatBackground(UploadChatBackgroundRequest $request, UpdateUserSettings $action): JsonResponse
    {
        $settings = $action->handle(
            $request->user(),
            [],
            null,
            $request->file('background'),
        );

        return (new UserSettingsResource($settings))->response();
    }
}
