<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Security;

use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::for('chatify-messages', fn () => Limit::perMinute(2));
    }

    public function test_message_endpoints_are_rate_limited(): void
    {
        $sender = $this->createUser();
        $recipient = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($sender, $recipient);

        $this->actingAs($sender, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/messages")
            ->assertOk();

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", ['body' => 'first'])
            ->assertCreated();

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", ['body' => 'second'])
            ->assertStatus(429);
    }
}
