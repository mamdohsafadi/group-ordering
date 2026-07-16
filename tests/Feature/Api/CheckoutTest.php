<?php

namespace Tests\Feature\Api;

use App\Models\Dish;
use App\Models\GroupCartItem;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for POST /api/v1/group-orders/{id}/checkout (spec §8.8, US-007). */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private GroupOrder $groupOrder;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupOrder = GroupOrder::factory()->create();
        $this->member = User::factory()->create();

        $leaderPart = GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->groupOrder->leader_id,
        ]);
        $memberPart = GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->member->id,
        ]);

        $dish = Dish::factory()->create([
            'restaurant_id' => $this->groupOrder->restaurant_id,
            'price' => 20.00,
            'active' => 1,
        ]);

        foreach ([[$leaderPart, 1], [$memberPart, 2]] as [$participant, $quantity]) {
            GroupCartItem::create([
                'group_order_id' => $this->groupOrder->id,
                'participant_id' => $participant->id,
                'dish_id' => $dish->id,
                'quantity' => $quantity,
                'unit_price' => 20.00,
                'total_price' => 20.00 * $quantity,
                'version' => 1,
            ]);
        }
    }

    private function checkout(User $user, array $payload = [])
    {
        return $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/checkout", $payload);
    }

    public function test_only_the_leader_can_submit(): void
    {
        $this->checkout($this->member)->assertStatus(403);
    }

    public function test_leader_submits_and_gets_the_contract_shape(): void
    {
        // Subtotals 60 + fee 10 + tax 3 = 73 charged to the leader (BR-005).
        $this->checkout($this->groupOrder->leader)
            ->assertOk()
            ->assertJson(['status' => 'CONFIRMED', 'total_charged' => 73.0])
            ->assertJsonCount(2, 'participant_breakdown')
            ->assertJsonStructure(['order_id', 'status', 'total_charged', 'participant_breakdown']);

        $fresh = $this->groupOrder->fresh();
        $this->assertSame(GroupOrder::STATUS_SUBMITTED, $fresh->status);
        $this->assertNotNull($fresh->bill_id);
        $this->assertNotNull($fresh->submitted_at);

        // Every cart line became an order row; invoices were stored (FR-022/023).
        $this->assertSame(0, $fresh->cartItems()->whereNull('order_id')->count());
        $this->assertSame(2, $fresh->invoices()->where('is_master', false)->count());
        $this->assertSame(1, $fresh->invoices()->where('is_master', true)->count());

        $this->assertDatabaseHas('bill', [
            'id' => $fresh->bill_id,
            'user_id' => $this->groupOrder->leader_id,
            'net_total' => 73.0,
        ]);
    }

    public function test_checkout_is_idempotent(): void
    {
        $first = $this->checkout($this->groupOrder->leader)->json();
        $second = $this->checkout($this->groupOrder->leader)->assertOk()->json();

        // A retry returns the same order instead of creating a duplicate.
        $this->assertSame($first['order_id'], $second['order_id']);
        $this->assertSame(1, GroupOrder::query()->whereKey($this->groupOrder->id)->whereNotNull('bill_id')->count());
        $this->assertDatabaseCount('bill', 1);
    }

    public function test_valid_promo_codes_discount_equally(): void
    {
        Voucher::create(['voucher' => 'SAVE6', 'value' => 6.00]);

        // 73 - 6 = 67; each of the two participants gets -3.00 (FR-021, BR-006).
        $this->checkout($this->groupOrder->leader, ['promo_code' => 'SAVE6'])
            ->assertOk()
            ->assertJson(['total_charged' => 67.0])
            ->assertJsonPath('participant_breakdown.0.discount_share', -3);
    }

    public function test_invalid_promo_codes_are_rejected(): void
    {
        $this->checkout($this->groupOrder->leader, ['promo_code' => 'NOPE'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired promo code. Please check and try again.']);

        $this->assertSame(GroupOrder::STATUS_ACTIVE, $this->groupOrder->fresh()->status);
    }

    public function test_empty_orders_cannot_be_submitted(): void
    {
        GroupCartItem::query()->delete();

        $this->checkout($this->groupOrder->leader)->assertStatus(422);
    }
}
