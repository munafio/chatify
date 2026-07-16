<?php

use Illuminate\Support\Facades\Route;

$controller = config('chatify.routes.namespace') . '\\MessagesController';

/**
 * Get shared photos
 */
Route::get('/test', [$controller, 'test'])->name('test');
