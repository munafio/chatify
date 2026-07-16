<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Broadcast;

use Chatify\Actions\Messages\SendMessage;
use Chatify\Events\MessageSent;
use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class MessageSentTest extends TestCase
{
    public function test_send_message_dispatches_broadcast_event(): void
    {
        Event::fake([MessageSent::class]);

        $a = $this->createUser();
        $b = $this->createUser();
        $conversation = app(ConversationService::class)->findOrCreateDirect($a, $b);

        app(SendMessage::class)->handle($conversation, $a, 'Hello');

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($conversation) {
            return $event->message->conversation_id === $conversation->id
                && $event->broadcastAs() === 'MessageSent';
        });
    }
}
