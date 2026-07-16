<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Web;

use Chatify\Tests\TestCase;

class MessengerWebTest extends TestCase
{
    public function test_authenticated_user_can_load_messenger_page(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->get('/chatify');

        $response->assertOk()
            ->assertSee('id="chatify-app"', false)
            ->assertSee('data-config', false);
    }

    public function test_guest_is_redirected_from_messenger(): void
    {
        $this->get('/chatify')
            ->assertRedirect('/login');
    }

    public function test_invalid_conversation_uuid_returns_404(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get('/chatify/not-a-uuid')
            ->assertNotFound();
    }
}
