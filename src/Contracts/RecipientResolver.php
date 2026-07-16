<?php

declare(strict_types=1);

namespace Chatify\Contracts;

use Illuminate\Database\Eloquent\Model;

interface RecipientResolver
{
    public function canMessage(Model $sender, Model $recipient): bool;
}
