<?php

namespace Chatify\Traits;

use App\Models\ChMessage as Message;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

trait HasMessage
{
    /**
     * Get the entity's read message.
     *
     * @return Builder
     */
    public function readMessage()
    {
        return $this->messages()->read();
    }

    /**
     * Get the user who sent the message.
     *
     * @return BelongsTo
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'to_id');
    }

    /**
     * Get the entity's unread message.
     *
     * @return Builder
     */
    public function unreadMessage()
    {
        return $this->messages()->unread();
    }
}
