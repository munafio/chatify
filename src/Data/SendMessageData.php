<?php

declare(strict_types=1);

namespace Chatify\Data;

final readonly class SendMessageData
{
    public function __construct(
        public ?string $body,
        public ?string $attachmentPath = null,
        public ?array $attachmentMeta = null,
        public ?string $replyToMessageId = null,
        public ?string $forwardedFromMessageId = null,
    ) {}
}
