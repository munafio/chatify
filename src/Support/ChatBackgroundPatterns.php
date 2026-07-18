<?php

declare(strict_types=1);

namespace Chatify\Support;

final class ChatBackgroundPatterns
{
    public static function all(): array
    {
        if (! ChatifyAppearanceConfig::isEnabled('chat_background')) {
            return [];
        }

        $entries = config('chatify.chat_background.patterns', []);
        $baseUrl = self::baseUrl();
        $patterns = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $name = trim((string) ($entry['name'] ?? ''));
            $filename = trim((string) ($entry['filename'] ?? ''));

            if ($name === '' || ! self::isSafeFilename($filename)) {
                continue;
            }

            $id = pathinfo($filename, PATHINFO_FILENAME);

            if ($id === '') {
                continue;
            }

            $patterns[] = [
                'id' => $id,
                'name' => $name,
                'url' => $baseUrl.'/'.$filename,
            ];
        }

        return $patterns;
    }

    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    private static function baseUrl(): string
    {
        $configured = config('chatify.chat_background.patterns_url');

        if (is_string($configured) && $configured !== '') {
            if (str_starts_with($configured, 'http://') || str_starts_with($configured, 'https://')) {
                return rtrim($configured, '/');
            }

            return rtrim(asset(ltrim($configured, '/')), '/');
        }

        return rtrim(asset('vendor/chatify/patterns'), '/');
    }

    private static function isSafeFilename(string $filename): bool
    {
        return preg_match('/^[a-zA-Z0-9._-]+\.(svg|png|webp)$/i', $filename) === 1;
    }
}
