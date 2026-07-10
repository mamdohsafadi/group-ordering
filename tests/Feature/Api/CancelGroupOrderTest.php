<?php

namespace Tests\Feature\Api;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for POST /api/v1/group-orders/{id}/cancel (handoff §2.5, BR-012). */
class CancelGroupOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/group-orders/1/cancel')->assertStatus(401);
    }

    public function test_unknown_ids_return_404(): void
    {
        $user = User::factory()->create();

        $this->withToken((string) $user->id)
            ->postJson('/api/v1/group-orders/999/cancel')
            ->assertStatus(404);
    }

    public function test_only_the_leader_can_cancel(): void
    {
        $groupOrder = GroupOrder::factory()->create();
        $member = User::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $groupOrder->id,
            'user_id' => $member->id,
        ]);

        $this->withToken((string) $member->id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/cancel")
            ->assertStatus(403)
            ->assertJson(['message' => 'Only the group leader can cancel the group order.']);
    }

    public function test_submitted_orders_cannot_be_cancelled(): void
    {
        $groupOrder = GroupOrder::factory()->submitted()->create();

        $this->withToken((string) $groupOrder->leader_id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/cancel")
            ->assertStatus(409)
            ->assertJson(['message' => 'Order has been submitted and cannot be modified.']);
    }

    public function test_the_leader_cancels_an_active_group(): void
    {
        $groupOrder = GroupOrder::factory()->create();

        $this->withToken((string) $groupOrder->leader_id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/cancel")
            ->assertOk()
            ->assertExactJson(['success' => true, 'status' => 'CANCELLED']);

        $this->assertSame(GroupOrder::STATUS_CANCELLED, $groupOrder->fresh()->status);
    }

    public function test_cancelling_twice_is_idempotent(): void
    {
        $groupOrder = GroupOrder::factory()->cancelled()->create();

        $this->withToken((string) $groupOrder->leader_id)
            ->postJson("/api/v1/group-orders/{$groupOrder->id}/cancel")
            ->assertOk()
            ->assertExactJson(['success' => true, 'status' => 'CANCELLED']);
    }
}
