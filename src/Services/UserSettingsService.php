<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\UserSetting;
use Chatify\Support\ChatifyAppearanceConfig;
use Chatify\Support\ChatifyModels;
use Chatify\Support\ThemePreferencesValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final class UserSettingsService
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
    ) {}

    public function forUser(Model $user): UserSetting
    {
        $settings = ChatifyModels::userSettingClass()::query()->firstOrCreate(
            ['user_id' => $user->getKey()],
            [
                'avatar' => config('chatify.user_avatar.default', 'avatar.png'),
                'dark_mode' => false,
                'messenger_color' => ChatifyAppearanceConfig::defaultColor(),
                'active_status' => true,
            ]
        );

        return $settings;
    }

    public function update(
        Model $user,
        array $attributes,
        ?UploadedFile $avatar = null,
        ?UploadedFile $chatBackground = null,
    ): UserSetting {
        $settings = $this->forUser($user);

        if ($avatar !== null) {
            $this->deleteAvatarIfCustom($settings->avatar);
            $stored = $this->attachmentService->storeAvatar($avatar);
            $settings->avatar = $stored['stored_name'];
        }

        if ($chatBackground !== null) {
            if (! ChatifyAppearanceConfig::isEnabled('chat_background')) {
                throw new \InvalidArgumentException('Wallpaper upload is disabled.');
            }

            $this->deleteChatBackgroundIfCustom($settings->chat_background);
            $stored = $this->attachmentService->storeChatBackground($chatBackground);
            $settings->chat_background = $stored['stored_name'];
        }

        if (array_key_exists('dark_mode', $attributes)) {
            $settings->dark_mode = (bool) $attributes['dark_mode'];
        }

        if (array_key_exists('active_status', $attributes)) {
            $settings->active_status = (bool) $attributes['active_status'];

            if (! $settings->active_status) {
                app(PresenceService::class)->markOffline($user);
            }
        }

        if (array_key_exists('messenger_color', $attributes)) {
            $settings->messenger_color = $attributes['messenger_color'];
        }

        if (! empty($attributes['reset_avatar'])) {
            $this->deleteAvatarIfCustom($settings->avatar);
            $settings->avatar = config('chatify.user_avatar.default', 'avatar.png');
        }

        if (array_key_exists('theme_preferences', $attributes)) {
            $validated = ThemePreferencesValidator::validate($attributes['theme_preferences']);
            $existing = is_array($settings->theme_preferences) ? $settings->theme_preferences : [];
            $settings->theme_preferences = array_replace_recursive($existing, $validated ?? []);

            if (isset($settings->theme_preferences['accentColor'])) {
                $settings->messenger_color = $settings->theme_preferences['accentColor'];
            }

            if (isset($settings->theme_preferences['themeId'])) {
                $settings->dark_mode = in_array($settings->theme_preferences['themeId'], ['night', 'tinted'], true);
            }
        }

        $settings->save();

        return $settings->fresh();
    }

    private function deleteAvatarIfCustom(?string $filename): void
    {
        $default = config('chatify.user_avatar.default', 'avatar.png');

        if ($filename === null || $filename === '' || $filename === $default) {
            return;
        }

        $path = config('chatify.user_avatar.folder', 'users-avatar').'/'.$filename;

        if ($this->attachmentService->storage()->exists($path)) {
            $this->attachmentService->storage()->delete($path);
        }
    }

    private function deleteChatBackgroundIfCustom(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = config('chatify.chat_background.folder', 'chat-backgrounds').'/'.$filename;

        if ($this->attachmentService->storage()->exists($path)) {
            $this->attachmentService->storage()->delete($path);
        }
    }
}
