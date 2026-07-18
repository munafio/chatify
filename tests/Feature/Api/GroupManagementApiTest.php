<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Models\Conversation;
use Chatify\Models\ConversationParticipant;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;

class GroupManagementApiTest extends TestCase
{
    public function test_limited_admin_cannot_rename_group(): void
    {
        $owner = $this->createUser();
        $limitedAdmin = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Ops',
            [(int) $limitedAdmin->getKey()],
        );

        $participant = $conversation->participants()->where('user_id', $limitedAdmin->id)->first();
        $participant?->forceFill([
            'role' => ConversationParticipant::ROLE_ADMIN,
            'permissions' => ['edit_info' => false, 'add_members' => true],
        ])->save();

        $this->actingAs($limitedAdmin, 'sanctum')
            ->patchJson("/api/chatify/v1/conversations/{$conversation->id}", [
                'name' => 'Renamed',
            ])
            ->assertForbidden();
    }

    public function test_owner_can_transfer_ownership_and_former_owner_can_leave(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Transfer Test',
            [(int) $member->getKey()],
        );

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/transfer-ownership", [
                'user_id' => $member->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.attributes.my_membership.role', ConversationParticipant::ROLE_ADMIN);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/chatify/v1/conversations/{$conversation->id}/leave")
            ->assertOk();

        $conversation->refresh();
        $this->assertSame((int) $owner->id, (int) $conversation->created_by);
        $this->assertTrue(
            $conversation->participants()->where('user_id', $member->id)->where('role', ConversationParticipant::ROLE_OWNER)->exists()
        );
        $this->assertFalse(
            $conversation->participants()->where('user_id', $owner->id)->exists()
        );
    }

    public function test_paginated_members_and_attachments_endpoints(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Paged',
            [(int) $member->getKey()],
        );

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/participants?per_page=1")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/attachments?type=media&page=1")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_message_search_requires_query(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Search',
            [(int) $member->getKey()],
        );

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/chatify/v1/conversations/{$conversation->id}/messages/search?q=a")
            ->assertStatus(422);
    }
}
