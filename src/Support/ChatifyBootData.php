<?php

declare(strict_types=1);

namespace Chatify\Support;

use Chatify\Http\Resources\UserResource;
use Chatify\Services\AttachmentService;
use Chatify\Services\BlockService;
use Chatify\Services\UserSettingsService;
use Illuminate\Http\Request;

final class ChatifyBootData
{
    public static function fromRequest(Request $request, ?string $conversationId = null): array
    {
        $user = $request->user();
        $locale = ChatifyLocale::current();
        $fallback = ChatifyLocale::fallback();

        $broadcast = config('chatify.frontend.broadcast', []);
        $settings = app(UserSettingsService::class)->forUser($user);
        $attachments = app(AttachmentService::class);
        $blockService = app(BlockService::class);

        return [
            'user' => (new UserResource($user))->resolve($request),
            'messaging_blocked_user_ids' => $blockService->blockedUserIdsFor($user),
            'default_avatar_url' => $blockService->defaultAvatarUrl(),
            'apiBase' => url('/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/')),
            'broadcastAuthUrl' => url('/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/').'/broadcasting/auth'),
            'csrfToken' => csrf_token(),
            'conversationId' => $conversationId,
            'webBase' => url('/'.trim(config('chatify.web.prefix', 'chatify'), '/')),
            'locale' => $locale,
            'fallbackLocale' => $fallback,
            'dir' => ChatifyLocale::direction($locale),
            'translations' => ChatifyLocale::translationPayload($locale),
            'fallbackTranslations' => $locale !== $fallback
                ? ChatifyLocale::translationPayload($fallback)
                : null,
            'appName' => __('chatify::chatify.ui.app_name', ['name' => config('chatify.name', 'Chatify Messenger')]),
            'debug' => (bool) config('app.debug', false),
            'groupsEnabled' => (bool) config('chatify.groups.enabled', true),
            'features' => ChatifyAppearanceConfig::features(),
            'colors' => ChatifyAppearanceConfig::colorValues(),
            'themes' => ChatifyAppearanceConfig::themeIds(),
            'fonts' => ChatifyAppearanceConfig::fontIds(),
            'wallpaperPatterns' => ChatBackgroundPatterns::all(),
            'preferences' => [
                'dark_mode' => (bool) $settings->dark_mode,
                'theme_preferences' => $settings->theme_preferences,
                'chat_background_url' => $attachments->chatBackgroundUrl($settings->chat_background),
                'show_online_status' => (bool) $settings->active_status,
            ],
            'attachments' => [
                'maxUploadSize' => (int) config('chatify.attachments.max_upload_size', 150),
                'allowedImages' => config('chatify.attachments.allowed_images', []),
                'allowedFiles' => config('chatify.attachments.allowed_files', []),
            ],
            'giphy' => [
                'enabled' => ChatifyAppearanceConfig::features()['giphy'],
                'apiKey' => ChatifyAppearanceConfig::giphyApiKey(),
            ],
            'broadcast' => [
                'driver' => in_array(config('broadcasting.default', 'null'), ['pusher', 'reverb'], true)
                    ? config('broadcasting.default')
                    : 'null',
                'key' => $broadcast['key'] ?? null,
                'cluster' => $broadcast['cluster'] ?? null,
                'wsHost' => $broadcast['wsHost'] ?? null,
                'wsPort' => (int) ($broadcast['wsPort'] ?? 443),
                'forceTLS' => (bool) ($broadcast['forceTLS'] ?? true),
            ],
            'sounds' => [
                'enabled' => (bool) config('chatify.sounds.enabled', true),
                'incomingMessage' => [
                    'enabled' => (bool) config('chatify.sounds.incoming_message.enabled', true),
                    'url' => config('chatify.sounds.incoming_message.url'),
                ],
                'outgoingMessage' => [
                    'enabled' => (bool) config('chatify.sounds.outgoing_message.enabled', true),
                    'url' => config('chatify.sounds.outgoing_message.url'),
                ],
                'typing' => [
                    'enabled' => (bool) config('chatify.sounds.typing.enabled', false),
                    'url' => config('chatify.sounds.typing.url'),
                ],
            ],
            'savedMessages' => [
                'enabled' => (bool) config('chatify.saved_messages.enabled', true),
                'title' => __('chatify::chatify.ui.saved_messages'),
            ],
        ];
    }
}
