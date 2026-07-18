<?php

declare(strict_types=1);

namespace Chatify\Tests\Feature\Api;

use Chatify\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class ConversationPinTest extends TestCase
{
    public function test_user_can_pin_and_reorder_conversations(): void
    {
        $user = $this->createUser();
        $otherA = $this->createUser();
        $otherB = $this->createUser();

        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/direct', ['user_id' => $otherA->id])
            ->json('data.id');

        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/chatify/v1/conversations/direct', ['user_id' => $otherB->id])
            ->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/chatify/v1/conversations/{$first}/pin", ['pinned' => true])
            ->assertOk()
            ->assertJsonPath('data.attributes.is_pinned', true);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/chatify/v1/conversations/{$second}/pin", ['pinned' => true])
            ->assertOk();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/chatify/v1/conversations/pin-order', [
                'conversation_ids' => [$second, $first],
            ])
            ->assertOk()
            ->assertJsonPath('data.reordered', true);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/chatify/v1/conversations');

        $response->assertOk();
        $ids = collect($response->json('data'))
            ->reject(fn (array $item) => ($item['attributes']['conversation_type'] ?? null) === 'saved')
            ->pluck('id')
            ->all();
        $this->assertSame([$second, $first], array_slice($ids, 0, 2));
    }
}
