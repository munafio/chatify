<?php

declare(strict_types=1);

namespace Chatify\Support;

final class ThemePreferencesValidator
{
    public static function validate(?array $preferences): ?array
    {
        if ($preferences === null) {
            return null;
        }

        if (! is_array($preferences)) {
            throw new \InvalidArgumentException('Theme preferences must be an array.');
        }

        $themes = ChatifyAppearanceConfig::themeIds();
        $patterns = ChatBackgroundPatterns::ids();
        $fonts = ChatifyAppearanceConfig::fontIds();
        $maxBytes = 4096;

        if (strlen(json_encode($preferences) ?: '') > $maxBytes) {
            throw new \InvalidArgumentException('Theme preferences payload is too large.');
        }

        $validated = [];

        if (array_key_exists('themeId', $preferences)) {
            if (! ChatifyAppearanceConfig::isEnabled('themes')) {
                throw new \InvalidArgumentException('Theme selection is disabled.');
            }

            $themeId = (string) $preferences['themeId'];
            if (! in_array($themeId, $themes, true)) {
                throw new \InvalidArgumentException('Invalid theme id.');
            }
            $validated['themeId'] = $themeId;
        }

        if (array_key_exists('accentColor', $preferences)) {
            if (! ChatifyAppearanceConfig::isEnabled('colors')) {
                throw new \InvalidArgumentException('Accent color selection is disabled.');
            }

            $validated['accentColor'] = self::validateHexColor((string) $preferences['accentColor']);
        }

        if (array_key_exists('fontFamily', $preferences)) {
            if (! ChatifyAppearanceConfig::isEnabled('fonts')) {
                throw new \InvalidArgumentException('Font selection is disabled.');
            }

            $fontFamily = (string) $preferences['fontFamily'];
            if ($fonts !== [] && ! in_array($fontFamily, $fonts, true)) {
                throw new \InvalidArgumentException('Invalid font family.');
            }
            $validated['fontFamily'] = $fontFamily;
        }

        if (array_key_exists('wallpaper', $preferences) && is_array($preferences['wallpaper'])) {
            if (! ChatifyAppearanceConfig::isEnabled('chat_background')) {
                throw new \InvalidArgumentException('Wallpaper selection is disabled.');
            }

            $validated['wallpaper'] = self::validateWallpaper($preferences['wallpaper'], $patterns);
        }

        return $validated;
    }

    private static function validateWallpaper(array $wallpaper, array $patterns): array
    {
        $validated = [];

        if (array_key_exists('kind', $wallpaper)) {
            $kind = (string) $wallpaper['kind'];
            if (! in_array($kind, ['none', 'pattern', 'image'], true)) {
                throw new \InvalidArgumentException('Invalid wallpaper kind.');
            }
            $validated['kind'] = $kind;
        }

        if (array_key_exists('patternId', $wallpaper)) {
            $patternId = (string) $wallpaper['patternId'];
            if (! in_array($patternId, $patterns, true)) {
                throw new \InvalidArgumentException('Invalid wallpaper pattern.');
            }
            $validated['patternId'] = $patternId;
        }

        if (array_key_exists('blurEnabled', $wallpaper)) {
            $validated['blurEnabled'] = filter_var($wallpaper['blurEnabled'], FILTER_VALIDATE_BOOLEAN);
        }

        if (array_key_exists('blurAmount', $wallpaper)) {
            $blurAmount = (int) $wallpaper['blurAmount'];
            if ($blurAmount < 0 || $blurAmount > 100) {
                throw new \InvalidArgumentException('Wallpaper blur amount must be between 0 and 100.');
            }
            $validated['blurAmount'] = $blurAmount;
        }

        return $validated;
    }

    private static function validateHexColor(string $color): string
    {
        $allowed = ChatifyAppearanceConfig::colorValues();
        $normalized = strtolower($color);

        if (in_array($normalized, array_map('strtolower', $allowed), true)) {
            return $normalized;
        }

        if (preg_match('/^#[0-9a-f]{6}$/i', $color) === 1) {
            return strtolower($color);
        }

        throw new \InvalidArgumentException('Invalid color value.');
    }
}
