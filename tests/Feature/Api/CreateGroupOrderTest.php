<?php

namespace Tests\Feature\Api;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for POST /api/v1/group-orders (handoff §2.1, spec §8.1). */
class CreateGroupOrderTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(User $user): array
    {
        return [
            'restaurant_id' => Restaurant::factory()->create()->id,
            'delivery_address_id' => UserAddress::factory()->create(['user_id' => $user->id])->id,
            'delivery_time_type' => 'ASAP',
            'scheduled_time' => null,
        ];
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/group-orders', [])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_creates_an_active_session_with_leader_enrolled(): void
    {
        $this->freezeSecond();

        $user = User::factory()->create();

        $response = $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders', $this->validPayload($user))
            ->assertCreated()
            ->assertJsonStructure(['group_order_id', 'shareable_link', 'expires_at', 'status'])
            ->assertJson(['status' => 'CREATED']);

        // NFR-006: 16 random bytes, hex encoded.
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $response->json('shareable_link'));
        $this->assertSame(now()->addMinutes(5)->toISOString(), $response->json('expires_at'));

        $groupOrder = GroupOrder::findOrFail($response->json('group_order_id'));
        $this->assertSame(GroupOrder::STATUS_ACTIVE, $groupOrder->status);
        $this->assertSame($user->id, $groupOrder->leader_id);

        // BR-006: the leader participates in every split, so they are enrolled.
        $this->assertDatabaseHas('group_participants', [
            'group_order_id' => $groupOrder->id,
            'user_id' => $user->id,
            'status' => GroupParticipant::STATUS_JOINED,
        ]);
    }

    public function test_scheduled_orders_require_a_future_scheduled_time(): void
    {
        $user = User::factory()->create();

        $payload = [...$this->validPayload($user), 'delivery_time_type' => 'SCHEDULED'];

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('scheduled_time');

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders', [
                ...$payload,
                'scheduled_time' => now()->addHour()->toISOString(),
            ])
            ->assertCreated();
    }

    public function test_rejects_inactive_restaurants(): void
    {
        $user = User::factory()->create();

        $payload = [
            ...$this->validPayload($user),
            'restaurant_id' => Restaurant::factory()->inactive()->create()->id,
        ];

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('restaurant_id');
    }

    public function test_rejects_addresses_belonging_to_other_users(): void
    {
        $user = User::factory()->create();

        $payload = [
            ...$this->validPayload($user),
            'delivery_address_id' => UserAddress::factory()->create()->id, // someone else's
        ];

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('delivery_address_id');
    }
}
