<?php

declare(strict_types=1);

namespace Chatify\Support;

use Chatify\Http\Resources\UserResource;
use Chatify\Services\AttachmentService;
use Chatify\Services\UserSettingsService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

final class ChatifyBootData
{
    public static function fromRequest(Request $request, ?string $conversationId = null): array
    {
        /** @var Authenticatable $user */
        $user = $request->user();

        $broadcast = config('chatify.frontend.broadcast', []);
        $settings = app(UserSettingsService::class)->forUser($user);
        $attachments = app(AttachmentService::class);

        return [
            'user' => (new UserResource($user))->resolve($request),
            'apiBase' => url('/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/')),
            'broadcastAuthUrl' => url('/'.trim(config('chatify.api.prefix', 'api/chatify/v1'), '/').'/broadcasting/auth'),
            'csrfToken' => csrf_token(),
            'conversationId' => $conversationId,
            'appName' => config('chatify.name', 'Chatify Messenger'),
            'debug' => (bool) config('app.debug', false),
            'groupsEnabled' => (bool) config('chatify.groups.enabled', true),
            'colors' => config('chatify.colors', []),
            'preferences' => [
                'dark_mode' => (bool) $settings->dark_mode,
                'theme_preferences' => $settings->theme_preferences,
                'chat_background_url' => $attachments->chatBackgroundUrl($settings->chat_background),
            ],
            'attachments' => [
                'maxUploadSize' => (int) config('chatify.attachments.max_upload_size', 150),
                'allowedImages' => config('chatify.attachments.allowed_images', []),
                'allowedFiles' => config('chatify.attachments.allowed_files', []),
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
        ];
    }
}
