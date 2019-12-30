<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messenger app name
    |--------------------------------------------------------------------------
    |
    | This value is the name of the app which is used in the views or elsewhere
    | in this app.
    |
    */

    'name' => env('CHATIFY_NAME', 'Chatify Messenger'),

    /*
    |--------------------------------------------------------------------------
    | Package path
    |--------------------------------------------------------------------------
    |
    | This value is the path of the package or in other meaning, it is the prefix
    | of all the registered routes in this package.
    |
    | e.g. : app.test/chatify
    */

    'path' => env('CHATIFY_PATH', 'chatify'),

    /*
    |--------------------------------------------------------------------------
    | Package's web routes middleware
    |--------------------------------------------------------------------------
    |
    | This value is the middleware of all routes registered in this package
    | which is by default : auth
    |
    */

    'middleware' => env('CHATIFY_MIDDLEWARE', 'auth'),

    /*
    |--------------------------------------------------------------------------
    | Pusher API credentials
    |--------------------------------------------------------------------------
    |
    | This array includes all the credentials that required to use pusher API
    | with Chatty package, which is used to broadcast events over websockets to
    | create a real-time features.
    |
    */
    'pusher' => [
        'key' => env('PUSHER_APP_KEY'),
        'secret' => env('PUSHER_APP_SECRET'),
        'app_id' => env('PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'encrypted' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Avatar
    |--------------------------------------------------------------------------
    |
    | This is the user's avatar setting that includes :
    | [folder]  which is the default folder name to upload and get
    |           user's avatar from.
    | [default] which is the default avatar file name for users stored
    |           in database.
    |
    */
    'user_avatar' => [
        'folder' => 'users-avatar',
        'default' => 'avatar.png',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments By Default
    |--------------------------------------------------------------------------
    |
    | This array contains the important default values that used in this package.
    |
    | The first value in this array is the default folder name in the storage
    | which is all the attachments will be stored in.
    | This is also going to be used in attachments urls in the views.
    |
    */
    'attachments' => [
        'folder' => 'attachments',
        // Below is the route name to download attachments.
        'route' => 'attachments.download',
    ],
];
