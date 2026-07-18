<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Support\ChatifyModels;
use Chatify\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlock extends ChatifyModel
{
    use HasUuid;

    protected $table = 'ch_user_blocks';

    protected $fillable = [
        'id',
        'blocker_id',
        'blocked_user_id',
    ];

    protected function tableConfigKey(): ?string
    {
        return 'blocks';
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'blocker_id');
    }

    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'blocked_user_id');
    }
}
