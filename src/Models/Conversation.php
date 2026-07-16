<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Support\ChatifyModels;
use Chatify\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends ChatifyModel
{
    use HasUuid;

    public const TYPE_DIRECT = 'direct';

    public const TYPE_GROUP = 'group';

    protected $table = 'ch_conversations';

    protected $fillable = [
        'id',
        'type',
        'name',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function tableConfigKey(): ?string
    {
        return 'conversations';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChatifyModels::participantClass());
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatifyModels::messageClass());
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            ChatifyModels::userClass(),
            config('chatify.tables.participants', 'ch_conversation_participants'),
            'conversation_id',
            'user_id'
        )->withPivot(['last_read_at', 'id'])->withTimestamps();
    }

    public function scopeDirect(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_DIRECT);
    }

    public function scopeGroup(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_GROUP);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q->where('user_id', $userId));
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }
}
