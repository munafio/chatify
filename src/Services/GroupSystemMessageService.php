<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Models\Conversation;
use Chatify\Models\Message;
use Illuminate\Database\Eloquent\Model;

final class GroupSystemMessageService
{
    public function __construct(
        private readonly MessageService $messageService,
    ) {}

    public function record(
        Conversation $conversation,
        Model $actor,
        string $event,
        array $targetUserIds,
        string $body,
    ): Message {
        return $this->messageService->sendSystemMessage(
            $conversation,
            $actor,
            $event,
            $targetUserIds,
            $body,
        );
    }
}
