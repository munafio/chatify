<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Support\ChatifyModels;
use Chatify\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PresenceTest extends TestCase
{
    public function test_heartbeat_marks_user_online_when_enabled(): void
    {
        $user = $this->createUser();

        ChatifyModels::userSettingClass()::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['active_status' => true],
        );

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/chatify/v1/presence/heartbeat')
            ->assertOk()
            ->assertJsonPath('data.online', true);

        $this->assertTrue(Cache::has('chatify:presence:'.$user->id));
    }

    public function test_offline_clears_presence_cache(): void
    {
        $user = $this->createUser();

        ChatifyModels::userSettingClass()::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['active_status' => true],
        );

        Cache::put('chatify:presence:'.$user->id, now()->timestamp, 45);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/chatify/v1/presence/offline')
            ->assertOk()
            ->assertJsonPath('data.online', false);

        $this->assertFalse(Cache::has('chatify:presence:'.$user->id));
    }

    public function test_user_can_update_show_online_status_setting(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/chatify/v1/settings', ['active_status' => false])
            ->assertOk()
            ->assertJsonPath('data.attributes.active_status', false);
    }

    public function test_new_user_has_active_status_enabled_by_default(): void
    {
        $user = $this->createUser();

        $settings = app(\Chatify\Services\UserSettingsService::class)->forUser($user);

        $this->assertTrue($settings->active_status);
    }
}
