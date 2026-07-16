<?php

use Illuminate\Support\Facades\Route;

$controller = config('chatify.api_routes.namespace') . '\\MessagesController';

/**
 * Authentication for pusher private channels
 */
Route::post('/chat/auth', [$controller, 'pusherAuth'])->name('api.pusher.auth');

/**
 *  Fetch info for specific id [user/group]
 */
Route::post('/idInfo', [$controller, 'idFetchData'])->name('api.idInfo');

/**
 * Send message route
 */
Route::post('/sendMessage', [$controller, 'send'])->name('api.send.message');

/**
 * Fetch messages
 */
Route::post('/fetchMessages', [$controller, 'fetch'])->name('api.fetch.messages');

/**
 * Download attachments route to create a downloadable links
 */
Route::get('/download/{fileName}', [$controller, 'download'])->name('api.'.config('chatify.attachments.download_route_name'));

/**
 * Make messages as seen
 */
Route::post('/makeSeen', [$controller, 'seen'])->name('api.messages.seen');

/**
 * Get contacts
 */
Route::get('/getContacts', [$controller, 'getContacts'])->name('api.contacts.get');

/**
 * Star in favorite list
 */
Route::post('/star', [$controller, 'favorite'])->name('api.star');

/**
 * get favorites list
 */
Route::post('/favorites', [$controller, 'getFavorites'])->name('api.favorites');

/**
 * Search in messenger
 */
Route::get('/search', [$controller, 'search'])->name('api.search');

/**
 * Get shared photos
 */
Route::post('/shared', [$controller, 'sharedPhotos'])->name('api.shared');

/**
 * Delete Conversation
 */
Route::post('/deleteConversation', [$controller, 'deleteConversation'])->name('api.conversation.delete');

/**
 * Update setting
 */
Route::post('/updateSettings', [$controller, 'updateSettings'])->name('api.avatar.update');

/**
 * Set active status
 */
Route::post('/setActiveStatus', [$controller, 'setActiveStatus'])->name('api.activeStatus.set');
