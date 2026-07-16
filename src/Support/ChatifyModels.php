<?php

declare(strict_types=1);

namespace Chatify\Support;

use Illuminate\Database\Eloquent\Model;

final class ChatifyModels
{
    public static function userClass(): string
    {
        return (string) config('chatify.models.user');
    }

    public static function conversationClass(): string
    {
        return (string) config('chatify.models.conversation');
    }

    public static function messageClass(): string
    {
        return (string) config('chatify.models.message');
    }

    public static function participantClass(): string
    {
        return (string) config('chatify.models.participant');
    }

    public static function favoriteClass(): string
    {
        return (string) config('chatify.models.favorite');
    }

    public static function userSettingClass(): string
    {
        return (string) config('chatify.models.user_setting');
    }

    public static function user(): Model
    {
        $class = self::userClass();

        return new $class;
    }
}
