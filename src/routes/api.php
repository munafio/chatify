<?php

declare(strict_types=1);

use Chatify\Http\Controllers\Api\AttachmentController;
use Chatify\Http\Controllers\Api\BroadcastAuthController;
use Chatify\Http\Controllers\Api\ContactController;
use Chatify\Http\Controllers\Api\ConversationController;
use Chatify\Http\Controllers\Api\FavoriteController;
use Chatify\Http\Controllers\Api\MessageController;
use Chatify\Http\Controllers\Api\TypingController;
use Chatify\Http\Controllers\Api\UserController;
use Chatify\Http\Controllers\Api\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(config('chatify.api.middleware', ['api', 'auth:sanctum']))
    ->prefix(config('chatify.api.prefix', 'api/chatify/v1'))
    ->group(function () {
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations/direct', [ConversationController::class, 'storeDirect']);
        Route::post('conversations/group', [ConversationController::class, 'storeGroup']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::patch('conversations/{conversation}', [ConversationController::class, 'update']);
        Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy']);
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markRead']);
        Route::post('conversations/{conversation}/typing', [TypingController::class, 'store'])
            ->middleware('throttle:chatify-messages');
        Route::post('conversations/{conversation}/forward', [MessageController::class, 'forward'])
            ->middleware('throttle:chatify-messages');
        Route::post('conversations/{conversation}/participants', [ConversationController::class, 'addParticipants']);
        Route::delete('conversations/{conversation}/participants/{user}', [ConversationController::class, 'removeParticipant']);
        Route::post('conversations/{conversation}/leave', [ConversationController::class, 'leave']);

        Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])
            ->middleware('throttle:chatify-messages');
        Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])
            ->middleware('throttle:chatify-messages');
        Route::patch('messages/{message}', [MessageController::class, 'update'])
            ->middleware('throttle:chatify-messages');
        Route::delete('messages/{message}', [MessageController::class, 'destroy']);

        Route::get('contacts/search', [ContactController::class, 'search']);

        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/{user}', [FavoriteController::class, 'toggle']);

        Route::get('users/{user}', [UserController::class, 'show']);

        Route::get('settings', [UserSettingsController::class, 'show']);
        Route::post('settings/avatar', [UserSettingsController::class, 'updateAvatar'])
            ->middleware('throttle:chatify-uploads');
        Route::post('settings/chat-background', [UserSettingsController::class, 'updateChatBackground'])
            ->middleware('throttle:chatify-uploads');
        Route::put('settings', [UserSettingsController::class, 'update'])
            ->middleware('throttle:chatify-uploads');
        Route::patch('settings', [UserSettingsController::class, 'update'])
            ->middleware('throttle:chatify-uploads');

        Route::get('conversations/{conversation}/attachments', [AttachmentController::class, 'index']);
        Route::get('attachments/{filename}', [AttachmentController::class, 'download']);

        Route::post('broadcasting/auth', BroadcastAuthController::class);
    });
