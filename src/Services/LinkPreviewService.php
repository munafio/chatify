<?php

declare(strict_types=1);

namespace Chatify\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

final class LinkPreviewService
{
    private const TIMEOUT_SECONDS = 5;

    private const MAX_BYTES = 524288;

    public function fetch(string $url): array
    {
        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->withHeaders([
                'User-Agent' => 'Chatify Link Preview',
                'Accept' => 'text/html,application/xhtml+xml',
            ])
            ->get($url);

        if (! $response->successful()) {
            return $this->emptyPreview($url);
        }

        $html = $response->body();

        if (strlen($html) > self::MAX_BYTES) {
            $html = substr($html, 0, self::MAX_BYTES);
        }

        return $this->parseHtml($url, $html);
    }

    private function parseHtml(string $url, string $html): array
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $title = $this->metaContent($xpath, 'og:title')
            ?? $this->metaContent($xpath, 'twitter:title')
            ?? $this->nodeText($xpath, '//title');

        $description = $this->metaContent($xpath, 'og:description')
            ?? $this->metaContent($xpath, 'twitter:description')
            ?? $this->metaContent($xpath, 'description');

        $image = $this->metaContent($xpath, 'og:image')
            ?? $this->metaContent($xpath, 'twitter:image');

        if ($image !== null) {
            $image = $this->resolveUrl($url, $image);
        }

        return [
            'url' => $url,
            'title' => $this->normalizeText($title),
            'description' => $this->normalizeText($description),
            'image' => $image,
        ];
    }

    private function emptyPreview(string $url): array
    {
        return [
            'url' => $url,
            'title' => null,
            'description' => null,
            'image' => null,
        ];
    }

    private function metaContent(DOMXPath $xpath, string $property): ?string
    {
        $nodes = $xpath->query(
            "//meta[@property='{$property}' or @name='{$property}']/@content"
        );

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->nodeValue);

        return $value !== '' ? $value : null;
    }

    private function nodeText(DOMXPath $xpath, string $query): ?string
    {
        $nodes = $xpath->query($query);

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $value = trim((string) $nodes->item(0)?->textContent);

        return $value !== '' ? $value : null;
    }

    private function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $value !== '' ? $value : null;
    }

    private function resolveUrl(string $baseUrl, string $relativeUrl): string
    {
        if (preg_match('#^https?://#i', $relativeUrl) === 1) {
            return $relativeUrl;
        }

        $parts = parse_url($baseUrl);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $relativeUrl;
        }

        $origin = $parts['scheme'].'://'.$parts['host'];

        if (isset($parts['port'])) {
            $origin .= ':'.$parts['port'];
        }

        if (str_starts_with($relativeUrl, '//')) {
            return $parts['scheme'].':'.$relativeUrl;
        }

        if (str_starts_with($relativeUrl, '/')) {
            return $origin.$relativeUrl;
        }

        $path = $parts['path'] ?? '/';
        $directory = str_contains($path, '/') ? substr($path, 0, (int) strrpos($path, '/') + 1) : '/';

        return $origin.$directory.$relativeUrl;
    }
}
