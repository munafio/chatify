<?php

declare(strict_types=1);

namespace Chatify\Traits;

use Chatify\Actions\Conversations\FindOrCreateDirectConversation;
use Chatify\Actions\Messages\SendMessage;
use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Chatify\Models\UserSetting;
use Chatify\Services\ConversationService;
use Chatify\Services\UserSettingsService;
use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;

trait InteractsWithChatify
{
    public function chatifySettings(): HasOne
    {
        return $this->hasOne(ChatifyModels::userSettingClass(), 'user_id');
    }

    public function conversationParticipants(): HasMany
    {
        return $this->hasMany(ChatifyModels::participantClass(), 'user_id');
    }

    public function chatifyConversations(): BelongsToMany
    {
        return $this->belongsToMany(
            ChatifyModels::conversationClass(),
            config('chatify.tables.participants', 'ch_conversation_participants'),
            'user_id',
            'conversation_id'
        )->withPivot(['last_read_at', 'id'])->withTimestamps();
    }

    public function sentChatifyMessages(): HasMany
    {
        return $this->hasMany(ChatifyModels::messageClass(), 'user_id');
    }

    public function chatifySettingsOrCreate(): UserSetting
    {
        return app(UserSettingsService::class)->forUser($this);
    }

    public function conversationWith(self $other): Conversation
    {
        return app(FindOrCreateDirectConversation::class)->handle($this, $other);
    }

    public function sendMessageTo(self $recipient, ?string $body = null, ?UploadedFile $attachment = null): Message
    {
        $conversation = $this->conversationWith($recipient);

        return app(SendMessage::class)->handle($conversation, $this, $body, $attachment);
    }

    public function participatesInConversation(string $conversationId): bool
    {
        return app(ConversationService::class)->getParticipant(
            ChatifyModels::conversationClass()::query()->findOrFail($conversationId),
            (int) $this->getKey()
        ) !== null;
    }
}
