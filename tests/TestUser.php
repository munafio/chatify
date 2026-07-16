<?php

declare(strict_types=1);

namespace Chatify\Tests;

use Chatify\Traits\InteractsWithChatify;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class TestUser extends Authenticatable
{
    use HasApiTokens;
    use InteractsWithChatify;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];
}
