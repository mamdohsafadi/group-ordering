<?php

namespace Tests\Feature\Api;

use App\Models\Dish;
use App\Models\GroupCartItem;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for GET .../invoice and .../invoice/master (spec §8.6/§8.7, US-006). */
class InvoiceTest extends TestCase
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

    public function test_participant_sees_their_own_breakdown(): void
    {
        // Subtotals 20 + 40 = 60; fee 10 split 5/5; tax 5% of 60 = 3 split 1.50/1.50.
        $this->withToken((string) $this->member->id)
            ->getJson("/api/v1/group-orders/{$this->groupOrder->id}/invoice")
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJson([
                'subtotal' => 40.0,
                'delivery_fee_share' => 5.0,
                'tax_share' => 1.5,
                'discount_share' => 0,
                'total' => 46.5,
            ]);
    }

    public function test_master_invoice_is_leader_only(): void
    {
        // US-006 AC4: participants never see the consolidated view.
        $this->withToken((string) $this->member->id)
            ->getJson("/api/v1/group-orders/{$this->groupOrder->id}/invoice/master")
            ->assertStatus(403);
    }

    public function test_leader_gets_the_consolidated_master_view(): void
    {
        $this->withToken((string) $this->groupOrder->leader_id)
            ->getJson("/api/v1/group-orders/{$this->groupOrder->id}/invoice/master")
            ->assertOk()
            ->assertJsonCount(2, 'participants')
            ->assertJson([
                'grand_total' => 60.0,
                'delivery_fee' => 10.0,
                'tax' => 3.0,
                'discount' => 0,
                'total' => 73.0,
            ]);
    }

    public function test_outsiders_cannot_view_invoices(): void
    {
        $outsider = User::factory()->create();

        $this->withToken((string) $outsider->id)
            ->getJson("/api/v1/group-orders/{$this->groupOrder->id}/invoice")
            ->assertStatus(403);
    }
}
