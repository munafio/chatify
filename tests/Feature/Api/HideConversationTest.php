<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;

class HideConversationTest extends TestCase
{
    public function test_user_can_hide_direct_conversation_from_inbox(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($userA, $userB);

        $this->actingAs($userA, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/hide")
            ->assertOk()
            ->assertJsonPath('data.hidden', true);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($userB, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_new_message_restores_hidden_conversation_for_all_participants(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($userA, $userB);

        $this->actingAs($userA, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/hide")
            ->assertOk();

        $this->actingAs($userB, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'body' => 'Wake up inbox',
            ])
            ->assertCreated();

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_opening_existing_direct_conversation_unhides_it_for_requester(): void
    {
        $userA = $this->createUser();
        $userB = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($userA, $userB);

        $this->actingAs($userA, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/hide")
            ->assertOk();

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($userA, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/direct', ['user_id' => $userB->id])
            ->assertCreated();

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
