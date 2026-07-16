<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Security;

use Chatify\Actions\Messages\SendMessage;
use Chatify\Models\Conversation;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;

class IdorTest extends TestCase
{
    public function test_user_cannot_delete_another_users_message(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();

        $conversation = app(ConversationService::class)->findOrCreateDirect($a, $b);

        $message = app(SendMessage::class)->handle($conversation, $a, 'secret');

        $this->actingAs($b, 'sanctum')
            ->deleteJson('/api/chatify/v1/messages/'.$message->id)
            ->assertForbidden();
    }
}
