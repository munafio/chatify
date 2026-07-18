<?php

declare(strict_types=1);

namespace Chatify\Support;

final class ChatifyAppearanceConfig
{
    public static function isEnabled(string $section): bool
    {
        $value = config("chatify.{$section}.enabled", true);

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function list(string $section): array
    {
        if (! self::isEnabled($section)) {
            return [];
        }

        $list = config("chatify.{$section}.list", []);

        return is_array($list) ? array_values($list) : [];
    }

    public static function colorValues(): array
    {
        return self::list('colors');
    }

    public static function themeIds(): array
    {
        return self::list('themes');
    }

    public static function fontIds(): array
    {
        return self::list('fonts');
    }

    public static function defaultColor(): string
    {
        $colors = self::colorValues();

        return is_string($colors[0] ?? null) ? (string) $colors[0] : '#2180f3';
    }

    public static function giphyApiKey(): ?string
    {
        if (! self::isEnabled('giphy')) {
            return null;
        }

        $apiKey = config('chatify.giphy.api_key');

        return is_string($apiKey) && $apiKey !== '' ? $apiKey : null;
    }

    public static function features(): array
    {
        return [
            'giphy' => self::isEnabled('giphy') && self::giphyApiKey() !== null,
            'colors' => self::isEnabled('colors'),
            'themes' => self::isEnabled('themes'),
            'fonts' => self::isEnabled('fonts'),
            'wallpaper' => self::isEnabled('chat_background'),
        ];
    }
}
