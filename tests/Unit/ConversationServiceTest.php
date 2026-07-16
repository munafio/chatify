<?php

declare(strict_types=1);

namespace Chatify\Tests\Unit;

use Chatify\Services\ConversationService;
use Chatify\Tests\TestCase;
use Chatify\Tests\TestUser;

class ConversationServiceTest extends TestCase
{
    public function test_find_or_create_direct_is_idempotent(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $service = app(ConversationService::class);

        $first = $service->findOrCreateDirect($a, $b);
        $second = $service->findOrCreateDirect($b, $a);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(2, $first->participants()->count());
    }
}
