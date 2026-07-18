<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Support\ChatifyModels;
use Chatify\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends ChatifyModel
{
    use HasUuid;
    use SoftDeletes;

    protected $table = 'ch_messages';

    protected $fillable = [
        'id',
        'conversation_id',
        'user_id',
        'kind',
        'body',
        'attachment',
        'system_event',
        'edited_at',
        'reply_to_message_id',
        'forwarded_from_message_id',
    ];

    protected function casts(): array
    {
        return [
            'attachment' => 'array',
            'system_event' => 'array',
            'edited_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected function tableConfigKey(): ?string
    {
        return 'messages';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::conversationClass());
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'user_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(static::class, 'reply_to_message_id');
    }

    public function forwardedFrom(): BelongsTo
    {
        return $this->belongsTo(static::class, 'forwarded_from_message_id');
    }

    public function scopeForConversation(Builder $query, string $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeAfter(Builder $query, string $messageId): Builder
    {
        $message = static::query()->find($messageId);

        if ($message === null) {
            return $query;
        }

        return $query->where('created_at', '>', $message->created_at);
    }

    public function scopeWithSender(Builder $query): Builder
    {
        return $query->with('sender');
    }

    public function isReadByParticipant(?ConversationParticipant $participant): bool
    {
        if ($participant === null || $participant->last_read_at === null) {
            return false;
        }

        return $this->created_at <= $participant->last_read_at;
    }
}
