<?php

declare(strict_types=1);

namespace Chatify\Http\Controllers\Api;

use Chatify\Services\LinkPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class LinkPreviewController extends Controller
{
    public function show(Request $request, LinkPreviewService $linkPreviewService): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'url', 'max:2048'],
        ]);

        $url = $validated['url'];

        if (! $this->isAllowedUrl($url)) {
            throw ValidationException::withMessages([
                'url' => [__('chatify::chatify.errors.only_http_https_urls')],
            ]);
        }

        $preview = $linkPreviewService->fetch($url);

        return response()->json(['data' => $preview]);
    }

    private function isAllowedUrl(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ! $this->isPrivateIp($host);
        }

        return true;
    }

    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
