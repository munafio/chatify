<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Traits\HasUuid;

class MessageUserState extends ChatifyModel
{
    use HasUuid;

    protected $table = 'ch_message_user_states';

    protected $fillable = [
        'id',
        'message_id',
        'user_id',
        'hidden_at',
    ];

    protected function casts(): array
    {
        return [
            'hidden_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
