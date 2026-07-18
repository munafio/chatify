<?php

declare(strict_types=1);

namespace Chatify\Services;

use Chatify\Contracts\RecipientResolver;
use Illuminate\Database\Eloquent\Model;

final class DefaultRecipientResolver implements RecipientResolver
{
    public function __construct(
        private readonly BlockService $blockService,
    ) {}

    public function canMessage(Model $sender, Model $recipient): bool
    {
        if ($sender->getKey() === $recipient->getKey()) {
            return false;
        }

        return ! $this->blockService->eitherBlocked($sender, $recipient);
    }
}
