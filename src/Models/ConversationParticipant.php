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

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_MEMBER = 'member';

    protected $table = 'ch_conversation_participants';

    protected $fillable = [
        'id',
        'conversation_id',
        'user_id',
        'role',
        'permissions',
        'last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
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

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isFullAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN && $this->permissions === null;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === self::ROLE_OWNER) {
            return true;
        }

        if ($this->role === self::ROLE_MODERATOR) {
            return in_array($permission, ['add_members', 'remove_members'], true);
        }

        if ($this->role === self::ROLE_ADMIN) {
            if ($this->permissions === null) {
                return true;
            }

            return (bool) ($this->permissions[$permission] ?? false);
        }

        return false;
    }
}
