<?php

declare(strict_types=1);

namespace Chatify\Models;

use Chatify\Support\ChatifyModel;
use Chatify\Support\ChatifyModels;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends ChatifyModel
{
    protected $table = 'ch_user_settings';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'avatar',
        'dark_mode',
        'messenger_color',
        'theme_preferences',
        'chat_background',
        'active_status',
    ];

    protected $attributes = [
        'active_status' => true,
    ];

    protected function casts(): array
    {
        return [
            'dark_mode' => 'boolean',
            'active_status' => 'boolean',
            'theme_preferences' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function tableConfigKey(): ?string
    {
        return 'user_settings';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ChatifyModels::userClass(), 'user_id');
    }
}
