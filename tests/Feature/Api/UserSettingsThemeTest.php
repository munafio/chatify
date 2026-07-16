<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserSettingsThemeTest extends TestCase
{
    public function test_user_can_patch_theme_preferences(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/settings', [
            'theme_preferences' => [
                'themeId' => 'night',
                'accentColor' => '#2180f3',
                'fontFamily' => 'system',
                'wallpaper' => [
                    'kind' => 'pattern',
                    'patternId' => 'bubbles',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.attributes.dark_mode', true)
            ->assertJsonPath('data.attributes.theme_preferences.themeId', 'night')
            ->assertJsonPath('data.attributes.theme_preferences.wallpaper.patternId', 'bubbles');

        $this->assertDatabaseHas('ch_user_settings', [
            'user_id' => $user->id,
            'dark_mode' => true,
        ]);
    }

    public function test_invalid_theme_is_rejected(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/settings', [
            'theme_preferences' => [
                'themeId' => 'invalid-theme',
            ],
        ])->assertUnprocessable();
    }

    public function test_user_can_patch_wallpaper_blur_preferences(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/settings', [
            'theme_preferences' => [
                'wallpaper' => [
                    'kind' => 'pattern',
                    'patternId' => 'bubbles',
                    'blurEnabled' => true,
                    'blurAmount' => 40,
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.attributes.theme_preferences.wallpaper.blurEnabled', true)
            ->assertJsonPath('data.attributes.theme_preferences.wallpaper.blurAmount', 40);
    }

    public function test_invalid_wallpaper_blur_amount_is_rejected(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->patchJson('/api/chatify/v1/settings', [
            'theme_preferences' => [
                'wallpaper' => [
                    'blurAmount' => 150,
                ],
            ],
        ])->assertUnprocessable();
    }

    public function test_user_can_upload_chat_background(): void
    {
        Storage::fake('public');

        $user = $this->createUser();
        $file = UploadedFile::fake()->image('background.jpg', 800, 600);

        $response = $this->actingAs($user, 'sanctum')
            ->post('/api/chatify/v1/settings/chat-background', [
                'background' => $file,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['attributes' => ['chat_background_url']]]);

        $filename = $response->json('data.attributes.chat_background');
        $this->assertNotEmpty($filename);
        Storage::disk('public')->assertExists('chat-backgrounds/'.$filename);
    }
}
