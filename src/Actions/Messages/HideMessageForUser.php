<?php

declare(strict_types=1);

namespace Chatify\Actions\Messages;

use Chatify\Models\Message;
use Chatify\Services\MessageService;
use Illuminate\Database\Eloquent\Model;

final class HideMessageForUser
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    public function handle(Message $message, Model $user): void
    {
        $this->messageService->hideForUser($message, (int) $user->getKey());
    }
}
