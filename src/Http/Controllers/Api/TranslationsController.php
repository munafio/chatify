<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Support\ChatifyLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TranslationsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $locale = ChatifyLocale::current();
        $fallback = ChatifyLocale::fallback();

        return response()->json([
            'locale' => $locale,
            'fallbackLocale' => $fallback,
            'dir' => ChatifyLocale::direction($locale),
            'translations' => ChatifyLocale::translationPayload($locale),
            'fallbackTranslations' => $locale !== $fallback
                ? ChatifyLocale::translationPayload($fallback)
                : null,
        ]);
    }
}
