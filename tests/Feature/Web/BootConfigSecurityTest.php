<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Web;

use Chatify\Tests\TestCase;

class BootConfigSecurityTest extends TestCase
{
    public function test_boot_config_does_not_expose_secrets(): void
    {
        config([
            'chatify.frontend.broadcast.key' => 'public-key',
            'app.debug' => false,
        ]);
        putenv('PUSHER_APP_SECRET=super-secret-value');

        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/chatify');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('public-key', $content);
        $this->assertStringNotContainsString('super-secret-value', $content);
        $this->assertStringNotContainsString('PUSHER_APP_SECRET', $content);
    }
}
