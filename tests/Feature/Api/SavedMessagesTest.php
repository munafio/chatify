<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;

class SavedMessagesTest extends TestCase
{
    public function test_inbox_provisions_saved_messages_conversation(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk();

        $response->assertJsonPath('data.0.attributes.conversation_type', Conversation::TYPE_SAVED);
        $response->assertJsonPath('data.0.attributes.is_saved', true);
    }

    public function test_saved_messages_cannot_be_hidden_or_deleted(): void
    {
        $user = $this->createUser();
        $saved = app(ConversationService::class)->findOrCreateSavedForUser($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$saved->id}/hide")
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/chatify/v1/conversations/{$saved->id}")
            ->assertForbidden();
    }

    public function test_user_can_send_and_clear_saved_messages(): void
    {
        $user = $this->createUser();
        $saved = app(ConversationService::class)->findOrCreateSavedForUser($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$saved->id}/messages", [
                'body' => 'Note to self',
            ])
            ->assertCreated();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$saved->id}/messages")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$saved->id}/clear")
            ->assertOk()
            ->assertJsonPath('data.attributes.conversation_type', Conversation::TYPE_SAVED);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$saved->id}/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
