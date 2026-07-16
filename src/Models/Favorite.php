<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Support\ChatifyModels;
use Chatify\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends ChatifyModel
{
    use HasUuid;

    protected $table = 'ch_favorites';

    protected $fillable = [
        'id',
        'user_id',
        'favorite_user_id',
    ];

    protected function tableConfigKey(): ?string
    {
        return 'favorites';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'user_id');
    }

    public function favoriteUser(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'favorite_user_id');
    }
}
