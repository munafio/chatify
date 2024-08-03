# Configure Laravel Reverb with Chatify

## Introduction

This document will guide you on how to configure Laravel Reverb with Chatify.

## Installation

### 1. Install Laravel Reverb package

First, you need to install Laravel Reverb. You can install it via composer by running the following command:

```bash
composer require laravel/reverb

php artisan reverb:install
```


### 2. Tweak the Environment Variables

After installing the package, you need to set the following environment variables in your `.env` file:

```env
REVERB_APP_ID       = your-app-id
REVERB_APP_KEY      = your-app-key
REVERB_APP_SECRET   = your-app-secret
REVERB_HOST         = your-host
REVERB_PORT         = your-port (default: 8080)
REVERB_SCHEME       = https (default: http for non-ssl)
```

Change the Pusher environment variables to Reverb environment variables.

```env
PUSHER_APP_ID       = "${REVERB_APP_ID}"
PUSHER_APP_KEY      = "${REVERB_APP_KEY}"
PUSHER_APP_SECRET   = "${REVERB_APP_SECRET}"
PUSHER_HOST         = "${REVERB_HOST}"
PUSHER_PORT         = "${REVERB_PORT}"
PUSHER_SCHEME       = "${REVERB_SCHEME}"
PUSHER_APP_CLUSTER  = "mt1" (default: mt1)
BROADCAST_DRIVER    = reverb
```
That is it! You have successfully configured Laravel Reverb with Chatify.


## To use SSL in your Reverb server
If you want to use SSL in your Reverb server, you need to follow the steps below:

### 1. update server configuration in `config/reverb.php` file. as shown below.


```php

    'servers' => [

        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [
                    'verify_peer' => false,
                    'local_cert' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT', null),
                    'local_pk' => env('LARAVEL_WEBSOCKETS_SSL_LOCAL_PK', null),
                ],
            ],
            'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10_000),
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
                'server' => [
                    'url' => env('REDIS_URL'),
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => env('REDIS_PORT', '6379'),
                    'username' => env('REDIS_USERNAME'),
                    'password' => env('REDIS_PASSWORD'),
                    'database' => env('REDIS_DB', '0'),
                ],
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
        ],

    ],

```

### 2. update the `REVERB_SERVER_HOST` and `REVERB_SERVER_PORT` in your `.env` file.

```env
LARAVEL_WEBSOCKETS_SSL_LOCAL_CERT  =  <path to your certificate file >
LARAVEL_WEBSOCKETS_SSL_LOCAL_PK    =  <path to your public key file>
```
