<?php

declare(strict_types=1);

return [
    'name' => env('CHATIFY_NAME', 'Chatify Messenger'),

    'storage_disk_name' => env('CHATIFY_STORAGE_DISK', 'public'),

    'models' => [
        'user' => env('CHATIFY_USER_MODEL', App\Models\User::class),
        'conversation' => Chatify\Models\Conversation::class,
        'message' => Chatify\Models\Message::class,
        'participant' => Chatify\Models\ConversationParticipant::class,
        'favorite' => Chatify\Models\Favorite::class,
        'user_setting' => Chatify\Models\UserSetting::class,
    ],

    'tables' => [
        'conversations' => 'ch_conversations',
        'participants' => 'ch_conversation_participants',
        'messages' => 'ch_messages',
        'favorites' => 'ch_favorites',
        'user_settings' => 'ch_user_settings',
    ],

    'api' => [
        'prefix' => env('CHATIFY_API_PREFIX', 'api/chatify/v1'),
        'middleware' => array_map('trim', array_filter(explode(',', env(
            'CHATIFY_API_MIDDLEWARE',
            filter_var(env('CHATIFY_WEB_ENABLED', true), FILTER_VALIDATE_BOOL) ? 'web,auth' : 'api,auth:sanctum',
        )))),
        'expose_email' => env('CHATIFY_EXPOSE_EMAIL', false),
    ],

    'user_avatar' => [
        'folder' => 'users-avatar',
        'default' => 'avatar.png',
    ],

    'gravatar' => [
        'enabled' => true,
        'image_size' => 200,
        'imageset' => 'identicon',
    ],

    'attachments' => [
        'folder' => 'attachments',
        'allowed_images' => ['png', 'jpg', 'jpeg', 'gif'],
        'allowed_files' => ['zip', 'rar', 'txt'],
        'max_upload_size' => env('CHATIFY_MAX_FILE_SIZE', 150),
    ],

    'colors' => [
        '#2180f3',
        '#2196F3',
        '#00BCD4',
        '#3F51B5',
        '#673AB7',
        '#4CAF50',
        '#FFC107',
        '#FF9800',
        '#ff2522',
        '#9C27B0',
    ],

    'presets' => [
        'classic',
        'ocean',
        'sunset',
        'neon',
        'midnight',
    ],

    'themes' => [
        'classic',
        'day',
        'tinted',
        'night',
    ],

    'fonts' => [
        'system',
        'segoe',
        'arial',
        'helvetica',
        'georgia',
        'times',
        'courier',
        'verdana',
        'tahoma',
        'trebuchet',
        'palatino',
        'garamond',
        'consolas',
        'calibri',
        'cambria',
        'lucida',
        'impact',
        'comic',
    ],

    'chat_background' => [
        'folder' => 'chat-backgrounds',
        'patterns' => [
            'bubbles',
            'circuit-board',
            'glamorous',
            'hideout',
        ],
    ],

    'web' => [
        'enabled' => env('CHATIFY_WEB_ENABLED', true),
        'prefix' => env('CHATIFY_ROUTES_PREFIX', 'chatify'),
        'middleware' => ['web', 'auth'],
        'layout' => env('CHATIFY_WEB_LAYOUT', 'Chatify::layouts.app'),
    ],

    'frontend' => [
        'asset_url' => env('CHATIFY_ASSET_URL'),
        'api_middleware' => env('CHATIFY_WEB_API_MIDDLEWARE', 'web,auth'),
        'broadcast' => [
            'driver' => env('CHATIFY_BROADCAST_DRIVER', env('BROADCAST_CONNECTION', 'null')),
            'key' => env('PUSHER_APP_KEY'),
            'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
            'wsHost' => env('PUSHER_HOST'),
            'wsPort' => (int) env('PUSHER_PORT', 443),
            'forceTLS' => filter_var(env('PUSHER_APP_USETLS', true), FILTER_VALIDATE_BOOL),
        ],
    ],

    'groups' => [
        'enabled' => env('CHATIFY_GROUPS_ENABLED', true),
        'min_participants' => 2,
        'max_participants' => env('CHATIFY_GROUP_MAX_PARTICIPANTS', 50),
        'max_name_length' => 100,
    ],
];
