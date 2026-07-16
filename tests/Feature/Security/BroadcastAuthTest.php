<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Security;

use Chatify\Support\ChatifyModels;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    public function test_unauthenticated_broadcast_auth_is_rejected(): void
    {
        $this->postJson('/api/chatify/v1/broadcasting/auth', [
            'channel_name' => 'private-chatify.conversation.test',
            'socket_id' => '123.456',
        ])->assertUnauthorized();
    }

    public function test_non_participant_is_not_listed_as_conversation_member(): void
    {
        $owner = $this->createUser();
        $member = $this->createUser();
        $outsider = $this->createUser();

        $conversation = app(ConversationService::class)->createGroup(
            $owner,
            'Private',
            [(int) $member->getKey()],
        );

        $isParticipant = ChatifyModels::participantClass()::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $outsider->getKey())
            ->exists();

        $this->assertFalse($isParticipant);
    }
}
