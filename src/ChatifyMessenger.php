<?php

declare(strict_types=1);

namespace Chatify;

use Chatify\Actions\Conversations\FindOrCreateDirectConversation;
use Chatify\Actions\Messages\SendMessage;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class ChatifyMessenger
{
    public function findOrCreateDirect(Model $userA, Model $userB): Conversation
    {
        return app(FindOrCreateDirectConversation::class)->handle($userA, $userB);
    }

    public function sendMessage(Conversation $conversation, Model $sender, ?string $body = null, ?UploadedFile $attachment = null): Message
    {
        return app(SendMessage::class)->handle($conversation, $sender, $body, $attachment);
    }
}
