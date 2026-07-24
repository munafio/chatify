<?php

declare(strict_types=1);

namespace Chatify\Http\Resources;

use Chatify\Models\UserSetting;
use Chatify\Services\AttachmentService;
use Chatify\Services\BlockService;
use Chatify\Services\PresenceService;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $settingsService = app(UserSettingsService::class);
        $settings = $settingsService->forUser($this->resource);
        $viewer = $request->user();
        $blockService = app(BlockService::class);
        $hideIdentity = $viewer !== null && ! $blockService->shouldRevealIdentity($viewer, $this->resource);

        $data = [
            'type' => 'user',
            'id' => $this->resource->getKey(),
            'attributes' => [
                'name' => $hideIdentity ? BlockService::hiddenUserName() : $this->resource->name,
                'avatar' => $hideIdentity
                    ? $blockService->defaultAvatarUrl()
                    : $this->avatarUrl($settings),
            ],
        ];

        if ($hideIdentity) {
            $data['attributes']['is_identity_hidden'] = true;
        }

        if (! $hideIdentity && config('chatify.api.expose_email', false)) {
            $data['attributes']['email'] = $this->resource->email ?? null;
        }

        if ($viewer !== null) {
            $data['attributes']['is_blocked_by_me'] = $blockService->isBlocked($viewer, $this->resource);
            $data['attributes']['is_blocked_by_them'] = $blockService->isBlocked($this->resource, $viewer);

            if (! $hideIdentity) {
                $presenceService = app(PresenceService::class);
                $isOnline = $presenceService->visibleIsOnline($viewer, $this->resource);

                if ($isOnline !== null) {
                    $data['attributes']['is_online'] = $isOnline;
                }
            }
        }

        return $data;
    }

    private function avatarUrl(UserSetting $settings): string
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
