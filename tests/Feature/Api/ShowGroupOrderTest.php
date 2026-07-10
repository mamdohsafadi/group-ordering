<?php

namespace Tests\Feature\Api;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for GET /api/v1/group-orders/{id} (handoff §2.2). */
class ShowGroupOrderTest extends TestCase
{
    use RefreshDatabase;

    private function groupWithLeader(): GroupOrder
    {
        $groupOrder = GroupOrder::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $groupOrder->leader_id,
        ]);

        return $groupOrder;
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/group-orders/1')->assertStatus(401);
    }

    public function test_unknown_ids_return_404(): void
    {
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->getJson('/api/v1/group-orders/999')
            ->assertStatus(404)
            ->assertJson(['message' => 'Group order not found.']);
    }

    public function test_outsiders_are_forbidden(): void
    {
        $groupOrder = $this->groupWithLeader();
        $outsider = User::factory()->create();

        $this->withToken((string) $outsider->id)
            ->getJson("/api/v1/group-orders/{$groupOrder->id}")
            ->assertStatus(403);
    }

    public function test_leader_gets_the_exact_lobby_shape(): void
    {
        $groupOrder = $this->groupWithLeader();
        $leader = $groupOrder->leader;

        $this->withToken((string) $leader->id)
            ->getJson("/api/v1/group-orders/{$groupOrder->id}")
            ->assertOk()
            ->assertJsonStructure([
                'id', 'leader_id', 'leader_name', 'restaurant_id', 'restaurant_name',
                'delivery_address_id', 'delivery_time_type', 'scheduled_time', 'status',
                'shareable_link', 'promo_code', 'created_at', 'expires_at', 'submitted_at',
                'participants' => [['id', 'user_id', 'name', 'is_leader', 'status', 'joined_at']],
            ])
            ->assertJson([
                'id' => $groupOrder->id,
                'leader_id' => $leader->id,
                'leader_name' => $leader->name,
                'restaurant_name' => $groupOrder->restaurant->name,
                'status' => 'ACTIVE',
                'participants' => [
                    ['user_id' => $leader->id, 'is_leader' => true, 'status' => 'JOINED'],
                ],
            ]);
    }

    public function test_joined_participants_can_view_the_lobby(): void
    {
        $groupOrder = $this->groupWithLeader();
        $member = User::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $member->id,
        ]);

        $this->withToken((string) $member->id)
            ->getJson("/api/v1/group-orders/{$groupOrder->id}")
            ->assertOk()
            ->assertJsonCount(2, 'participants');
    }

    public function test_stale_sessions_expire_lazily_on_read(): void
    {
        // FR-005: window elapsed and nobody but the leader joined.
        $groupOrder = GroupOrder::factory()->windowElapsed()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $groupOrder->leader_id,
        ]);

        $this->withToken((string) $groupOrder->leader_id)
            ->getJson("/api/v1/group-orders/{$groupOrder->id}")
            ->assertOk()
            ->assertJson(['status' => 'EXPIRED']);

        $this->assertSame(GroupOrder::STATUS_EXPIRED, $groupOrder->fresh()->status);
    }

    public function test_sessions_with_participants_do_not_expire(): void
    {
        $groupOrder = GroupOrder::factory()->windowElapsed()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $groupOrder->leader_id,
        ]);
        GroupParticipant::factory()->create(['group_order_id' => $groupOrder->id]);

        $this->withToken((string) $groupOrder->leader_id)
            ->getJson("/api/v1/group-orders/{$groupOrder->id}")
            ->assertOk()
            ->assertJson(['status' => 'ACTIVE']);
    }
}
