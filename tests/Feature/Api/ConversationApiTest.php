<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Tests\TestCase;
use Chatify\Tests\TestUser;
use Illuminate\Support\Facades\Event;

class ConversationApiTest extends TestCase
{
    public function test_user_can_create_direct_conversation_and_send_message(): void
    {
        Event::fake();

        $sender = $this->createUser();
        $recipient = $this->createUser();

        $createResponse = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/direct', ['user_id' => $recipient->id]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.type', 'conversation');

        $conversationId = $createResponse->json('data.id');

        $messageResponse = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversationId}/messages", [
                'body' => 'Hello world',
            ]);

        $messageResponse->assertCreated()
            ->assertJsonPath('data.attributes.body', 'Hello world');
    }

    public function test_non_participant_cannot_view_conversation(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $intruder = $this->createUser();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/direct', ['user_id' => $other->id]);

        $conversationId = $response->json('data.id');

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversationId}")
            ->assertNotFound();
    }
}
