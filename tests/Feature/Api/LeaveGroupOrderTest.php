<?php

namespace Tests\Feature\Api;

use App\Models\Dish;
use App\Models\GroupCartItem;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for POST /api/v1/group-orders/{id}/leave (spec §8.9, US-008). */
class LeaveGroupOrderTest extends TestCase
{
    use RefreshDatabase;

    private GroupOrder $groupOrder;

    private User $member;

    private GroupParticipant $memberPart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupOrder = GroupOrder::factory()->create();
        $this->member = User::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->groupOrder->leader_id,
        ]);
        $this->memberPart = GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->member->id,
        ]);

        $dish = Dish::factory()->create(['restaurant_id' => $this->groupOrder->restaurant_id]);

        GroupCartItem::create([
            'group_order_id' => $this->groupOrder->id,
            'participant_id' => $this->memberPart->id,
            'dish_id' => $dish->id,
            'quantity' => 1,
            'unit_price' => 10.00,
            'total_price' => 10.00,
            'version' => 1,
        ]);
    }

    private function leave(User $user)
    {
        return $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/leave");
    }

    public function test_participant_leaves_and_their_cart_is_removed(): void
    {
        // US-008 AC2 / FR-011.
        $this->leave($this->member)
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'You have left the group order']);

        $fresh = $this->memberPart->fresh();
        $this->assertSame(GroupParticipant::STATUS_LEFT, $fresh->status);
        $this->assertNotNull($fresh->left_at);
        $this->assertSame(0, $fresh->cartItems()->count());
    }

    public function test_leaving_blocks_rejoining(): void
    {
        // BR-007, via the join endpoint.
        $this->leave($this->member)->assertOk();

        $this->withToken((string) $this->member->id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/join", [
                'link_token' => $this->groupOrder->shareable_link,
            ])->assertStatus(403);
    }

    public function test_the_leader_cannot_leave(): void
    {
        $this->leave($this->groupOrder->leader)
            ->assertStatus(403)
            ->assertJson(['message' => 'The group leader cannot leave the group order.']);
    }

    public function test_leaving_is_disabled_after_submission(): void
    {
        // US-008 AC4.
        $this->groupOrder->update(['status' => GroupOrder::STATUS_SUBMITTED]);

        $this->leave($this->member)->assertStatus(409);
    }

    public function test_outsiders_cannot_leave(): void
    {
        $this->leave(User::factory()->create())->assertStatus(403);
    }
}
