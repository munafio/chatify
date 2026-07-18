<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;

class GroupConversationApiTest extends TestCase
{
    public function test_user_can_create_and_rename_group(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();

        $create = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/group', [
                'name' => 'Team Chat',
                'user_ids' => [$member->id],
            ]);

        $create->assertCreated()
            ->assertJsonPath('data.attributes.conversation_type', Conversation::TYPE_GROUP)
            ->assertJsonPath('data.attributes.name', 'Team Chat');

        $conversationId = $create->json('data.id');

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/chatify/v1/conversations/{$conversationId}", [
                'name' => 'Renamed Team',
            ])
            ->assertOk()
            ->assertJsonPath('data.attributes.name', 'Renamed Team');
    }

    public function test_non_owner_cannot_rename_group(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Ops',
            [(int) $member->getKey()],
        );

        $this->actingAs($member, 'sanctum')
            ->patchJson("/api/chatify/v1/conversations/{$conversation->id}", [
                'name' => 'Hacked',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_add_and_remove_participant(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $newMember = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Project',
            [(int) $member->getKey()],
        );

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/participants", [
                'user_ids' => [$newMember->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.attributes.participant_count', 3);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/chatify/v1/conversations/{$conversation->id}/participants/{$newMember->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.participant_count', 2);
    }

    public function test_adding_participant_creates_system_message(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $newMember = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Project',
            [(int) $member->getKey()],
        );

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/participants", [
                'user_ids' => [$newMember->id],
            ])
            ->assertOk();

        $message = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('kind', 'system')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($message);
        $this->assertSame('participant_added', $message->system_event['event'] ?? null);
        $this->assertStringContainsString('added', (string) $message->body);
    }

    public function test_removing_participant_creates_system_message(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $newMember = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Project',
            [(int) $member->getKey(), (int) $newMember->getKey()],
        );

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/chatify/v1/conversations/{$conversation->id}/participants/{$newMember->id}")
            ->assertOk();

        $message = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('kind', 'system')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($message);
        $this->assertSame('participant_removed', $message->system_event['event'] ?? null);
        $this->assertStringContainsString('removed', (string) $message->body);
    }

    public function test_member_can_leave_group(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Leave Test',
            [(int) $member->getKey()],
        );

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/leave")
            ->assertOk()
            ->assertJsonPath('data.left', true);
    }
}
