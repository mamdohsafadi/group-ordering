<?php

namespace Tests\Feature\Api;

use App\Models\Dish;
use App\Models\GroupCartItem;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for PUT/DELETE /api/v1/group-orders/{id}/cart/items/{item} (spec §8.4/§8.5, US-005). */
class EditCartItemTest extends TestCase
{
    use RefreshDatabase;

    private GroupOrder $groupOrder;

    private User $member;

    private GroupCartItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupOrder = GroupOrder::factory()->create();
        $this->member = User::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->groupOrder->leader_id,
        ]);
        $participant = GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->member->id,
        ]);

        $dish = Dish::factory()->create([
            'restaurant_id' => $this->groupOrder->restaurant_id,
            'price' => 10.00,
            'active' => 1,
        ]);

        $this->item = GroupCartItem::create([
            'group_order_id' => $this->groupOrder->id,
            'participant_id' => $participant->id,
            'dish_id' => $dish->id,
            'quantity' => 2,
            'unit_price' => 10.00,
            'total_price' => 20.00,
            'version' => 1,
        ]);
    }

    private function updateItem(User $user, array $payload)
    {
        return $this->withToken((string) $user->id)
            ->putJson("/api/v1/group-orders/{$this->groupOrder->id}/cart/items/{$this->item->id}", $payload);
    }

    public function test_owner_updates_quantity_and_version_increments(): void
    {
        $this->updateItem($this->member, ['quantity' => 3, 'version' => 1])
            ->assertOk()
            ->assertJson(['cart_item_id' => $this->item->id, 'total_price' => 30.0, 'updated_subtotal' => 30.0]);

        $fresh = $this->item->fresh();
        $this->assertSame(3, $fresh->quantity);
        $this->assertSame(2, $fresh->version);
    }

    public function test_stale_versions_are_rejected(): void
    {
        // NFR-008: concurrent edits collide instead of silently overwriting.
        $this->updateItem($this->member, ['quantity' => 3, 'version' => 1])->assertOk();

        $this->updateItem($this->member, ['quantity' => 5, 'version' => 1])
            ->assertStatus(409)
            ->assertJson(['message' => 'This item was changed from another device. Refresh and try again.']);

        $this->assertSame(3, $this->item->fresh()->quantity);
    }

    public function test_quantity_zero_removes_the_item(): void
    {
        // US-005 AC2.
        $this->updateItem($this->member, ['quantity' => 0, 'version' => 1])
            ->assertOk()
            ->assertJson(['total_price' => 0, 'updated_subtotal' => 0]);

        $this->assertDatabaseMissing('group_cart_items', ['id' => $this->item->id]);
    }

    public function test_other_participants_cannot_touch_the_item(): void
    {
        $other = User::factory()->create();
        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $other->id,
        ]);

        $this->updateItem($other, ['quantity' => 9, 'version' => 1])->assertStatus(403);

        $this->withToken((string) $other->id)
            ->deleteJson("/api/v1/group-orders/{$this->groupOrder->id}/cart/items/{$this->item->id}")
            ->assertStatus(403);
    }

    public function test_the_leader_can_edit_any_sub_cart(): void
    {
        // Spec §2: leader edit/remove covers "Own + all".
        $this->updateItem($this->groupOrder->leader, ['quantity' => 1, 'version' => 1])
            ->assertOk()
            ->assertJson(['total_price' => 10.0, 'updated_subtotal' => 10.0]);
    }

    public function test_owner_removes_an_item(): void
    {
        // US-005 AC3 — response per spec §8.5.
        $this->withToken((string) $this->member->id)
            ->deleteJson("/api/v1/group-orders/{$this->groupOrder->id}/cart/items/{$this->item->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'updated_subtotal' => 0]);

        $this->assertDatabaseMissing('group_cart_items', ['id' => $this->item->id]);
    }

    public function test_editing_locks_after_submission(): void
    {
        // FR-016 / US-005 AC4.
        $this->groupOrder->update(['status' => GroupOrder::STATUS_SUBMITTED]);

        $this->updateItem($this->member, ['quantity' => 3, 'version' => 1])
            ->assertStatus(409)
            ->assertJson(['message' => 'Order has been submitted and cannot be modified.']);
    }

    public function test_version_is_required(): void
    {
        $this->updateItem($this->member, ['quantity' => 3])->assertStatus(422);
    }
}
