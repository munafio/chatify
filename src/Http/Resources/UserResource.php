<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\UserSetting;
use Chatify\Services\AttachmentService;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Model */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settingsService = app(UserSettingsService::class);
        $settings = $settingsService->forUser($this->resource);

        $data = [
            'type' => 'user',
            'id' => $this->resource->getKey(),
            'attributes' => [
                'name' => $this->resource->name,
                'avatar' => $this->avatarUrl($settings),
                'active_status' => $settings->active_status,
            ],
        ];

        if (config('chatify.api.expose_email', false)) {
            $data['attributes']['email'] = $this->resource->email ?? null;
        }

        return $data;
    }

    private function avatarUrl($settings): string
    {
        if ($settings->avatar === config('chatify.user_avatar.default', 'avatar.png')
            && config('chatify.gravatar.enabled', true)
            && isset($this->resource->email)) {
            $size = config('chatify.gravatar.image_size', 200);
            $set = config('chatify.gravatar.imageset', 'identicon');

            return 'https://www.gravatar.com/avatar/'.md5(strtolower(trim((string) $this->resource->email))).'?s='.$size.'&d='.$set;
        }

        $folder = config('chatify.user_avatar.folder', 'users-avatar');

        return app(AttachmentService::class)->storage()->url($folder.'/'.$settings->avatar);
    }
}
