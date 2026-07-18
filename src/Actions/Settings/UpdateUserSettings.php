<?php

declare(strict_types=1);

namespace Chatify\Actions\Settings;

use Chatify\Models\UserSetting;
use Chatify\Services\UserSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class UpdateUserSettings
{
    public function __construct(
        private readonly UserSettingsService $userSettingsService,
    ) {}

    public function handle(
        Model $user,
        array $attributes,
        ?UploadedFile $avatar = null,
        ?UploadedFile $chatBackground = null,
    ): UserSetting {
        return $this->userSettingsService->update($user, $attributes, $avatar, $chatBackground);
    }
}
