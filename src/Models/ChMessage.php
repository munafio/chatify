<?php

namespace App\Models;

use Chatify\MessageCollection;
use Chatify\Traits\UUID;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotificationCollection;

class ChMessage extends Model
{
    use UUID;

    /**
     * Get the user who sent the message.
     *
     * @return BelongsTo
     */
    public function from()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    /**
     * Get the user who received the message.
     *
     * @return BelongsTo
     */
    public function to()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    /**
     * Mark the notification as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        if ($this->seen !== 1) {
            $this->forceFill(['seen' => 1])->save();
        }
    }

    /**
     * Mark the notification as unread.
     *
     * @return void
     */
    public function markAsUnread()
    {
        if ($this->seen !== 0) {
            $this->forceFill(['seen' => 0])->save();
        }
    }

    /**
     * Determine if a notification has been read.
     *
     * @return bool
     */
    public function read()
    {
        return $this->seen !== 0;
    }

    /**
     * Determine if a notification has not been read.
     *
     * @return bool
     */
    public function unread()
    {
        return $this->seen === 0;
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @return Builder
     */
    public function scopeRead(Builder $query)
    {
        return $query->where('seen', 1);
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @return Builder
     */
    public function scopeUnread(Builder $query)
    {
        return $query->where('seen', 0);
    }

    /**
     * Create a new database notification collection instance.
     *
     * @return DatabaseNotificationCollection
     */
    public function newCollection(array $models = [])
    {
        return new MessageCollection($models);
    }
}
