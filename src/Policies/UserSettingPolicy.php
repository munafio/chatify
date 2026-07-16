<?php

declare(strict_types=1);

namespace Chatify\Policies;

use Chatify\Models\UserSetting;
use Illuminate\Database\Eloquent\Model;

final class UserSettingPolicy
{
    public function view(Model $user, UserSetting $setting): bool
    {
        return (int) $setting->user_id === (int) $user->getKey();
    }

    public function update(Model $user, UserSetting $setting): bool
    {
        return (int) $setting->user_id === (int) $user->getKey();
    }
}
