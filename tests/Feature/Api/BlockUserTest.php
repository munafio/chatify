<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Events\UserTyping;
use Chatify\Events\ConversationInboxUpdated;
use Chatify\Services\ConversationService;
use Chatify\Support\ChatifyBootData;
use Chatify\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class BlockUserTest extends TestCase
{
    public function test_user_can_block_and_is_excluded_from_search(): void
    {
        $blocker = $this->createUser(['name' => 'Blocker User']);
        $target = $this->createUser(['name' => 'Blocked Target']);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.blocked', true);

        $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/chatify/v1/contacts/search?q=Blocked')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_blocked_user_cannot_start_direct_conversation(): void
    {
        $blocker = $this->createUser();
        $target = $this->createUser();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $this->actingAs($blocker, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/direct', ['user_id' => $target->id])
            ->assertForbidden();
    }

    public function test_blocks_index_includes_messaging_blocked_user_ids(): void
    {
        $blocker = $this->createUser();
        $target = $this->createUser();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/chatify/v1/blocks')
            ->assertOk()
            ->assertJsonPath('meta.messaging_blocked_user_ids.0', $target->id);
    }

    public function test_boot_payload_includes_messaging_blocked_user_ids(): void
    {
        $blocker = $this->createUser();
        $target = $this->createUser();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $blocker);

        $boot = ChatifyBootData::fromRequest($request, null);

        $this->assertContains($target->id, $boot['messaging_blocked_user_ids']);
    }

    public function test_direct_typing_is_suppressed_when_users_are_blocked(): void
    {
        Event::fake([UserTyping::class]);

        $blocker = $this->createUser();
        $target = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($blocker, $target);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $this->actingAs($target, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/typing", [
                'is_typing' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.typing', false);

        Event::assertNotDispatched(UserTyping::class);
    }

    public function test_message_index_hides_blocked_sender_in_direct_chat(): void
    {
        $blocker = $this->createUser();
        $target = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($blocker, $target);

        $this->actingAs($target, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'body' => 'From target',
            ])
            ->assertCreated();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'body' => 'From blocker',
            ])
            ->assertCreated();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $response = $this->actingAs($blocker, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/messages")
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame('From blocker', $response->json('data.0.attributes.body'));
    }

    public function test_message_index_hides_blocked_sender_in_group_chat_for_viewer_only(): void
    {
        $owner = $this->createUser();
        $memberA = $this->createUser();
        $memberB = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Team',
            [(int) $memberA->getKey(), (int) $memberB->getKey()],
        );

        $this->actingAs($memberB, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'body' => 'From member B',
            ])
            ->assertCreated();

        $this->actingAs($memberA, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'body' => 'From member A',
            ])
            ->assertCreated();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/messages", [
                'body' => 'From owner',
            ])
            ->assertCreated();

        $this->actingAs($memberA, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$memberB->id}")
            ->assertOk();

        $memberAResponse = $this->actingAs($memberA, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/messages")
            ->assertOk();

        $memberABodies = collect($memberAResponse->json('data'))
            ->pluck('attributes.body')
            ->all();

        $this->assertContains('From member A', $memberABodies);
        $this->assertContains('From owner', $memberABodies);
        $this->assertNotContains('From member B', $memberABodies);

        $ownerResponse = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/messages")
            ->assertOk();

        $ownerBodies = collect($ownerResponse->json('data'))
            ->pluck('attributes.body')
            ->all();

        $this->assertContains('From member B', $ownerBodies);
    }

    public function test_blocks_index_shows_real_identity_for_blocked_users(): void
    {
        $blocker = $this->createUser(['name' => 'Blocker User']);
        $target = $this->createUser(['name' => 'Blocked Target']);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/chatify/v1/blocks')
            ->assertOk()
            ->assertJsonPath('data.0.attributes.name', 'Blocked Target')
            ->assertJsonMissingPath('data.0.attributes.is_identity_hidden');
    }

    public function test_blocker_sees_blocked_user_with_real_identity_in_inbox(): void
    {
        $blocker = $this->createUser(['name' => 'Blocker User']);
        $target = $this->createUser(['name' => 'Blocked Target']);

        app(ConversationService::class)->findOrCreateDirect($blocker, $target);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $response = $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk();

        $otherUser = collect($response->json('data'))
            ->first(fn (array $item) => ($item['attributes']['conversation_type'] ?? null) === 'direct')['relationships']['other_user'];

        $this->assertSame('Blocked Target', $otherUser['attributes']['name']);
    }

    public function test_blocked_party_sees_blocker_as_unknown_user_in_inbox(): void
    {
        $blocker = $this->createUser(['name' => 'Blocker User']);
        $target = $this->createUser(['name' => 'Blocked Target']);

        app(ConversationService::class)->findOrCreateDirect($blocker, $target);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $response = $this->actingAs($target, 'sanctum')
            ->getJson('/api/chatify/v1/conversations')
            ->assertOk();

        $otherUser = collect($response->json('data'))
            ->first(fn (array $item) => ($item['attributes']['conversation_type'] ?? null) === 'direct')['relationships']['other_user'];

        $this->assertSame('Unknown User', $otherUser['attributes']['name']);
        $this->assertTrue($otherUser['attributes']['is_identity_hidden']);
    }

    public function test_blocked_user_is_excluded_from_favorites_list(): void
    {
        $blocker = $this->createUser();
        $target = $this->createUser();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/favorites/{$target->id}")
            ->assertOk();

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        $this->actingAs($blocker, 'sanctum')
            ->getJson('/api/chatify/v1/favorites')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_blocked_user_is_excluded_from_group_member_pagination_for_blocker(): void
    {
        $owner = $this->createUser();
        $memberA = $this->createUser(['name' => 'Member A']);
        $memberB = $this->createUser(['name' => 'Member B']);

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Team',
            [(int) $memberA->getKey(), (int) $memberB->getKey()],
        );

        $this->actingAs($memberA, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$memberB->id}")
            ->assertOk();

        $response = $this->actingAs($memberA, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/participants")
            ->assertOk();

        $memberIds = collect($response->json('data'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertNotContains((int) $memberB->getKey(), $memberIds);
        $this->assertContains((int) $memberA->getKey(), $memberIds);
        $this->assertContains((int) $owner->getKey(), $memberIds);
    }

    public function test_block_broadcasts_inbox_update_for_direct_conversation(): void
    {
        Event::fake([ConversationInboxUpdated::class]);

        $blocker = $this->createUser();
        $target = $this->createUser();

        app(ConversationService::class)->findOrCreateDirect($blocker, $target);

        $this->actingAs($blocker, 'sanctum')
            ->postJson("/api/chatify/v1/blocks/{$target->id}")
            ->assertOk();

        Event::assertDispatched(ConversationInboxUpdated::class, 2);
    }
}
