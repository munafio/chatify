<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Support\ChatifyModels;
use Chatify\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends ChatifyModel
{
    use HasUuid;

    protected $table = 'ch_conversation_participants';

    protected $fillable = [
        'id',
        'conversation_id',
        'user_id',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function tableConfigKey(): ?string
    {
        return 'participants';
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::conversationClass());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'user_id');
    }

    public function markRead(): void
    {
        $this->forceFill(['last_read_at' => now()])->save();
    }
}
