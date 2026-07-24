<?php

declare(strict_types=1);

namespace Chatify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocaleFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if ($locale !== null) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request): ?string
    {
        $config = config('chatify.locale', []);
        $detectVia = $config['detect_via'] ?? ['header', 'query', 'user', 'app'];

        foreach ($detectVia as $method) {
            $locale = match ($method) {
                'header' => $this->fromHeaders($request, $config),
                'query' => $this->fromQuery($request, $config),
                'user' => $this->fromUser($request, $config),
                'app' => app()->getLocale(),
                default => null,
            };

            if ($locale !== null && $locale !== '') {
                return $this->normalizeLocale($locale);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function fromHeaders(Request $request, array $config): ?string
    {
        $explicit = $config['fallback_header'] ?? 'X-Chatify-Locale';
        if ($request->headers->has($explicit)) {
            return (string) $request->headers->get($explicit);
        }

        $header = $config['header'] ?? 'Accept-Language';
        $value = $request->headers->get($header);

        if (! is_string($value) || $value === '') {
            return null;
        }

        $parts = explode(',', $value);

        foreach ($parts as $part) {
            $tag = trim(explode(';', $part)[0] ?? '');

            if ($tag === '') {
                continue;
            }

            return str_replace('_', '-', $tag);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function fromQuery(Request $request, array $config): ?string
    {
        $parameter = $config['query_parameter'] ?? 'locale';

        if (! $request->query->has($parameter)) {
            return null;
        }

        return (string) $request->query->get($parameter);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function fromUser(Request $request, array $config): ?string
    {
        $attribute = $config['user_attribute'] ?? 'locale';
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $value = data_get($user, $attribute);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = str_replace('_', '-', strtolower(trim($locale)));

        if (str_contains($locale, '-')) {
            [$language] = explode('-', $locale, 2);

            return $language;
        }

        return $locale;
    }
}
