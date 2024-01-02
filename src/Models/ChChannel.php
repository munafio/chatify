<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Chatify\Traits\UUID;

class ChChannel extends Model
{
    use UUID;

    protected $fillable = [
        'avatar'
    ];

	public function users(){
		return $this->belongsToMany(User::class, 'ch_channel_user', 'channel_id', 'user_id');
	}
}
