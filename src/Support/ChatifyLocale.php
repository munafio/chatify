<?php

declare(strict_types=1);

namespace Chatify\Support;

final class ChatifyLocale
{
    public static function current(): string
    {
        return app()->getLocale();
    }

    public static function fallback(): string
    {
        return (string) config('app.fallback_locale', 'en');
    }

    public static function direction(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }

    public static function isRtl(?string $locale = null): bool
    {
        $locale ??= self::current();

        return in_array($locale, config('chatify.rtl_locales', ['ar']), true);
    }

    /**
     * @param  array<string, scalar|null>  $replace
     */
    public static function trans(string $key, array $replace = []): string
    {
        if (! str_contains($key, '::')) {
            $key = 'chatify::chatify.'.$key;
        }

        return (string) __($key, $replace);
    }

    /**
     * @return array<string, mixed>
     */
    public static function lines(?string $locale = null): array
    {
        $locale ??= self::current();

        return app('translator')->getLoader()->load($locale, 'chatify', 'chatify');
    }

    /**
     * @return array<string, mixed>
     */
    public static function translationPayload(?string $locale = null): array
    {
        $locale ??= self::current();
        $previous = app()->getLocale();

        if ($locale !== $previous) {
            app()->setLocale($locale);
        }

        $lines = self::lines($locale);

        $payload = [
            'ui' => $lines['ui'] ?? [],
            'system_messages' => $lines['system_messages'] ?? [],
            'dates' => $lines['dates'] ?? [],
            'themes' => $lines['themes'] ?? [],
            'wallpaper' => $lines['wallpaper'] ?? [],
            'roles' => $lines['roles'] ?? [],
        ];

        if ($locale !== $previous) {
            app()->setLocale($previous);
        }

        return $payload;
    }
}
