<?php

namespace Chatify\Traits;

use App\Models\ChMessage as Message;

trait HasMessage
{
    /**
     * Get the user who sent the message.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'to_id');
    }

    /**
     * Get the entity's read message.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function readMessage()
    {
        return $this->messages()->read();
    }

    /**
     * Get the entity's unread message.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function unreadMessage()
    {
        return $this->messages()->unread();
    }
}
