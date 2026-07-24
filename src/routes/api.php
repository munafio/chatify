<?php

declare(strict_types=1);

use Chatify\Http\Controllers\Api\GroupParticipantController;
use Chatify\Http\Controllers\Api\AttachmentController;
use Chatify\Http\Controllers\Api\BlockController;
use Chatify\Http\Controllers\Api\BroadcastAuthController;
use Chatify\Http\Controllers\Api\ContactController;
use Chatify\Http\Controllers\Api\LinkPreviewController;
use Chatify\Http\Controllers\Api\ConversationController;
use Chatify\Http\Controllers\Api\FavoriteController;
use Chatify\Http\Controllers\Api\MessageController;
use Chatify\Http\Controllers\Api\PresenceController;
use Chatify\Http\Controllers\Api\TranslationsController;
use Chatify\Http\Controllers\Api\TypingController;
use Chatify\Http\Controllers\Api\UserController;
use Chatify\Http\Controllers\Api\UserSettingsController;
use Chatify\Http\Middleware\SetLocaleFromRequest;
use Illuminate\Support\Facades\Route;

$apiMiddleware = config('chatify.api.middleware', ['api', 'auth:sanctum']);

if (config('chatify.locale.middleware', true)) {
    $apiMiddleware = array_values(array_unique([
        SetLocaleFromRequest::class,
        ...$apiMiddleware,
    ]));
}

Route::middleware($apiMiddleware)
    ->prefix(config('chatify.api.prefix', 'api/chatify/v1'))
    ->group(function () {
        Route::get('translations', TranslationsController::class);

        Route::get('conversations', [ConversationController::class, 'index']);
        Route::put('conversations/pin-order', [ConversationController::class, 'reorderPinned']);
        Route::post('conversations/direct', [ConversationController::class, 'storeDirect']);
        Route::post('conversations/group', [ConversationController::class, 'storeGroup']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::patch('conversations/{conversation}', [ConversationController::class, 'update']);
        Route::post('conversations/{conversation}/avatar', [ConversationController::class, 'uploadAvatar'])
            ->middleware('throttle:chatify-uploads');
        Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy']);
        Route::post('conversations/{conversation}/hide', [ConversationController::class, 'hide']);
        Route::post('conversations/{conversation}/clear', [ConversationController::class, 'clear']);
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead']);
        Route::patch('conversations/{conversation}/pin', [ConversationController::class, 'pin']);
        Route::post('conversations/{conversation}/typing', [TypingController::class, 'store'])
            ->middleware('throttle:chatify-messages');
        Route::post('conversations/{conversation}/forward', [MessageController::class, 'forward'])
            ->middleware('throttle:chatify-messages');
        Route::post('conversations/{conversation}/participants', [ConversationController::class, 'addParticipants']);
        Route::get('conversations/{conversation}/participants', [GroupParticipantController::class, 'index']);
        Route::patch('conversations/{conversation}/participants/{user}', [GroupParticipantController::class, 'update']);
        Route::post('conversations/{conversation}/transfer-ownership', [GroupParticipantController::class, 'transferOwnership']);
        Route::delete('conversations/{conversation}/participants/{user}', [ConversationController::class, 'removeParticipant']);
        Route::post('conversations/{conversation}/leave', [ConversationController::class, 'leave']);

        Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])
            ->middleware('throttle:chatify-messages');
        Route::get('conversations/{conversation}/messages/search', [MessageController::class, 'search'])
            ->middleware('throttle:chatify-messages');
        Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])
            ->middleware('throttle:chatify-messages');
        Route::patch('messages/{message}', [MessageController::class, 'update'])
            ->middleware('throttle:chatify-messages');
        Route::delete('messages/{message}', [MessageController::class, 'destroy']);

        Route::get('contacts/search', [ContactController::class, 'search']);
        Route::get('link-preview', [LinkPreviewController::class, 'show']);

        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/{user}', [FavoriteController::class, 'toggle']);

        Route::get('blocks', [BlockController::class, 'index']);
        Route::post('blocks/{user}', [BlockController::class, 'store']);
        Route::delete('blocks/{user}', [BlockController::class, 'destroy']);

        Route::post('presence/heartbeat', [PresenceController::class, 'heartbeat']);
        Route::post('presence/offline', [PresenceController::class, 'offline']);

        Route::get('users/{user}', [UserController::class, 'show']);

        Route::get('settings', [UserSettingsController::class, 'show']);
        Route::post('settings/avatar', [UserSettingsController::class, 'updateAvatar'])
            ->middleware('throttle:chatify-uploads');
        Route::post('settings/chat-background', [UserSettingsController::class, 'updateChatBackground'])
            ->middleware('throttle:chatify-uploads');
        Route::patch('settings', [UserSettingsController::class, 'update'])
            ->middleware('throttle:chatify-uploads');

        Route::get('conversations/{conversation}/attachments', [AttachmentController::class, 'index']);
        Route::get('attachments/{filename}', [AttachmentController::class, 'download']);

        Route::post('broadcasting/auth', BroadcastAuthController::class);
    });
