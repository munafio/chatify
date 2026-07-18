<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class LinkPreviewTest extends TestCase
{
    public function test_link_preview_parses_open_graph_tags(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response(
                <<<'HTML'
                <!DOCTYPE html>
                <html>
                <head>
                    <meta property="og:title" content="Example Title" />
                    <meta property="og:description" content="Example Description" />
                    <meta property="og:image" content="https://example.com/image.jpg" />
                </head>
                <body></body>
                </html>
                HTML,
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/link-preview?url='.urlencode('https://example.com/page'))
            ->assertOk()
            ->assertJsonPath('data.url', 'https://example.com/page')
            ->assertJsonPath('data.title', 'Example Title')
            ->assertJsonPath('data.description', 'Example Description')
            ->assertJsonPath('data.image', 'https://example.com/image.jpg');
    }

    public function test_link_preview_rejects_invalid_url(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/link-preview?url=not-a-url')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_link_preview_rejects_localhost_urls(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/link-preview?url='.urlencode('http://127.0.0.1/admin'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    }

    public function test_link_preview_requires_authentication(): void
    {
        $this->getJson('/api/chatify/v1/link-preview?url='.urlencode('https://example.com/page'))
            ->assertUnauthorized();
    }
}
