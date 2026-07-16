<?php

declare(strict_types=1);

use Chatify\Http\Controllers\Web\MessengerController;
use Illuminate\Support\Facades\Route;

$prefix = config('chatify.web.prefix', 'chatify');
$middleware = config('chatify.web.middleware', ['web', 'auth']);

Route::middleware($middleware)
    ->prefix($prefix)
    ->group(function () {
        Route::get('/', [MessengerController::class, 'index'])->name('chatify.index');
        Route::get('/{conversationId}', [MessengerController::class, 'index'])
            ->whereUuid('conversationId')
            ->name('chatify.conversation');
    });
