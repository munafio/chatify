<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Support\ChatifyLocale;
use Chatify\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TranslationsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_translations_endpoint_returns_locale_payload(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/translations', [
                'X-Chatify-Locale' => 'ar',
            ]);

        $response->assertOk()
            ->assertJsonPath('locale', 'ar')
            ->assertJsonPath('dir', 'rtl')
            ->assertJsonPath('translations.ui.sidebar.chats', 'الدردشات')
            ->assertJsonStructure([
                'locale',
                'fallbackLocale',
                'dir',
                'translations' => [
                    'ui',
                    'system_messages',
                    'dates',
                    'themes',
                    'wallpaper',
                    'roles',
                ],
            ]);
    }

    public function test_block_user_error_is_translated_in_arabic(): void
    {
        $user = $this->createUser();

        app()->setLocale('ar');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/chatify/v1/blocks/'.$user->getKey(), [], [
                'X-Chatify-Locale' => 'ar',
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => __('chatify::chatify.errors.cannot_block_self'),
            ]);
    }

    public function test_chatify_locale_direction(): void
    {
        app()->setLocale('en');
        $this->assertSame('ltr', ChatifyLocale::direction());

        app()->setLocale('ar');
        $this->assertSame('rtl', ChatifyLocale::direction());
    }
}
