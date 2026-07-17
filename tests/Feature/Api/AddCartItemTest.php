<?php

namespace Tests\Feature\Api;

use App\Models\AppliedDishOption;
use App\Models\Dish;
use App\Models\DishOption;
use App\Models\DishOptionGroup;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contract tests for POST /api/v1/group-orders/{id}/cart/items (spec §8.3, US-004). */
class AddCartItemTest extends TestCase
{
    use RefreshDatabase;

    private GroupOrder $groupOrder;

    private User $member;

    private Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupOrder = GroupOrder::factory()->create();
        $this->member = User::factory()->create();

        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->groupOrder->leader_id,
        ]);
        GroupParticipant::factory()->create([
            'group_order_id' => $this->groupOrder->id,
            'user_id' => $this->member->id,
        ]);

        $this->dish = Dish::factory()->create([
            'restaurant_id' => $this->groupOrder->restaurant_id,
            'price' => 10.00,
            'active' => 1,
        ]);
    }

    private function addItem(User $user, array $payload)
    {
        return $this->withToken((string) $user->id)
            ->postJson("/api/v1/group-orders/{$this->groupOrder->id}/cart/items", $payload);
    }

    /** Creates an option applicable to the test dish. */
    private function applicableOption(float $price): DishOption
    {
        $group = DishOptionGroup::create([
            'restaurant_id' => $this->groupOrder->restaurant_id,
            'en_name' => 'Extras',
            'ar_name' => 'إضافات',
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $option = DishOption::create([
            'dish_group_id' => $group->id,
            'en_name' => 'Extra cheese',
            'ar_name' => 'جبنة إضافية',
            'price' => $price,
            'purchase_price' => $price,
            'is_default' => false,
            'is_active' => true,
            'is_deleted' => false,
        ]);

        AppliedDishOption::create([
            'dish_id' => $this->dish->id,
            'dish_option_id' => $option->id,
        ]);

        return $option;
    }

    public function test_requires_authentication(): void
    {
        $this->postJson("/api/v1/group-orders/{$this->groupOrder->id}/cart/items", [])
            ->assertStatus(401);
    }

    public function test_participant_adds_an_item_and_gets_the_contract_shape(): void
    {
        $this->addItem($this->member, ['menu_item_id' => $this->dish->id, 'quantity' => 2])
            ->assertStatus(201)
            ->assertJsonStructure(['cart_item_id', 'total_price', 'updated_subtotal'])
            ->assertJson(['total_price' => 20.0, 'updated_subtotal' => 20.0]);

        $this->assertDatabaseHas('group_cart_items', [
            'group_order_id' => $this->groupOrder->id,
            'dish_id' => $this->dish->id,
            'quantity' => 2,
            'version' => 1,
        ]);
    }

    public function test_subtotal_accumulates_across_own_items_only(): void
    {
        $this->addItem($this->member, ['menu_item_id' => $this->dish->id, 'quantity' => 2]);
        $this->addItem($this->groupOrder->leader, ['menu_item_id' => $this->dish->id, 'quantity' => 5]);

        // The member's second add sees only their own running subtotal (US-004 AC3/AC4).
        $this->addItem($this->member, ['menu_item_id' => $this->dish->id, 'quantity' => 1])
            ->assertStatus(201)
            ->assertJson(['updated_subtotal' => 30.0]);
    }

    public function test_modifiers_are_priced_and_snapshotted(): void
    {
        $option = $this->applicableOption(1.50);

        $response = $this->addItem($this->member, [
            'menu_item_id' => $this->dish->id,
            'quantity' => 2,
            'modifiers' => [$option->id],
            'special_instructions' => 'No onions please',
        ]);

        $response->assertStatus(201)->assertJson(['total_price' => 23.0]);

        $item = $this->groupOrder->cartItems()->firstOrFail();
        $this->assertSame(11.5, $item->unit_price);
        $this->assertSame([['id' => $option->id, 'name' => 'Extra cheese', 'price' => 1.5]], $item->modifiers);
    }

    public function test_lobby_exposes_only_the_callers_cart(): void
    {
        $this->addItem($this->member, ['menu_item_id' => $this->dish->id, 'quantity' => 2]);
        $this->addItem($this->groupOrder->leader, ['menu_item_id' => $this->dish->id, 'quantity' => 1]);

        $this->withToken((string) $this->member->id)
            ->getJson("/api/v1/group-orders/{$this->groupOrder->id}")
            ->assertOk()
            ->assertJsonCount(1, 'my_cart.items')
            ->assertJsonPath('my_cart.subtotal', 20)
            ->assertJsonPath('my_cart.items.0.dish_name', $this->dish->eng_name)
            ->assertJsonPath('my_cart.items.0.version', 1);
    }

    public function test_outsiders_cannot_add_items(): void
    {
        $outsider = User::factory()->create();

        $this->addItem($outsider, ['menu_item_id' => $this->dish->id, 'quantity' => 1])
            ->assertStatus(403);
    }

    public function test_cart_locks_after_submission(): void
    {
        // FR-016 / US-005 AC4 — message shown verbatim by the UI.
        $this->groupOrder->update(['status' => GroupOrder::STATUS_SUBMITTED]);

        $this->addItem($this->member, ['menu_item_id' => $this->dish->id, 'quantity' => 1])
            ->assertStatus(409)
            ->assertJson(['message' => 'Order has been submitted and cannot be modified.']);
    }

    public function test_dishes_from_other_restaurants_are_rejected(): void
    {
        // BR-003: no cross-restaurant group orders.
        $foreignDish = Dish::factory()->create([
            'restaurant_id' => Restaurant::factory()->create()->id,
        ]);

        $this->addItem($this->member, ['menu_item_id' => $foreignDish->id, 'quantity' => 1])
            ->assertStatus(422);
    }

    public function test_options_not_applicable_to_the_dish_are_rejected(): void
    {
        $option = $this->applicableOption(1.50);
        $otherDish = Dish::factory()->create([
            'restaurant_id' => $this->groupOrder->restaurant_id,
            'active' => 1,
        ]);

        $this->addItem($this->member, [
            'menu_item_id' => $otherDish->id,
            'quantity' => 1,
            'modifiers' => [$option->id],
        ])->assertStatus(422);
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $this->addItem($this->member, ['menu_item_id' => $this->dish->id, 'quantity' => 0])
            ->assertStatus(422);
    }
}
