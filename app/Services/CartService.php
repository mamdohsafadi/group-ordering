<?php

namespace App\Services;

use App\Models\AppliedDishOption;
use App\Models\Dish;
use App\Models\DishOption;
use App\Models\GroupCartItem;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Owns the sub-cart for US-004/US-005 (spec §8.3–§8.5, FR-009/FR-013..FR-016).
 * Prices are snapshotted onto the cart line at add time (dish price + selected
 * options), so later menu edits never change what a participant already added.
 *
 * Error messages are surfaced verbatim by the frontend; change them only
 * together with the UI.
 */
class CartService
{
    /**
     * US-004: add a dish (with modifiers) to the caller's own sub-cart.
     *
     * @return array{item: GroupCartItem, subtotal: float}
     */
    public function addItem(int $groupOrderId, User $user, array $data): array
    {
        return DB::transaction(function () use ($groupOrderId, $user, $data) {
            $groupOrder = GroupOrder::query()->whereKey($groupOrderId)->lockForUpdate()->first();

            if ($groupOrder === null) {
                abort(404, 'Group order not found.');
            }

            $participant = $groupOrder->participants()
                ->where('user_id', $user->id)
                ->where('status', GroupParticipant::STATUS_JOINED)
                ->first();

            if ($participant === null) {
                abort(403, 'You are not part of this group order.');
            }

            $this->assertEditable($groupOrder);

            $dish = Dish::query()->find($data['menu_item_id']);

            // BR-003: every sub-cart orders from the group's restaurant.
            if ($dish === null || $dish->restaurant_id !== $groupOrder->restaurant_id || $dish->active !== 1) {
                abort(422, "This dish is not available from the group's restaurant.");
            }

            $modifiers = $this->resolveModifiers($dish, $data['modifiers'] ?? []);
            $unitPrice = round($dish->price + array_sum(array_column($modifiers, 'price')), 2);
            $quantity = (int) $data['quantity'];

            $item = GroupCartItem::create([
                'group_order_id' => $groupOrder->id,
                'participant_id' => $participant->id,
                'dish_id' => $dish->id,
                'quantity' => $quantity,
                'modifiers' => $modifiers === [] ? null : $modifiers,
                'special_instructions' => $data['special_instructions'] ?? null,
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $quantity, 2),
                'version' => 1,
            ]);

            return ['item' => $item, 'subtotal' => $this->subtotalFor($participant)];
        });
    }

    /**
     * US-005 AC1/AC2: change quantity or modifiers on a cart line. Quantity 0
     * removes the item. Optimistic locking (NFR-008): the caller sends the
     * version it last saw; a mismatch means someone else changed the line.
     *
     * @return array{item: GroupCartItem|null, subtotal: float}
     */
    public function updateItem(int $groupOrderId, int $itemId, User $user, array $data): array
    {
        return DB::transaction(function () use ($groupOrderId, $itemId, $user, $data) {
            [$groupOrder, $participant, $item] = $this->findEditableItem($groupOrderId, $itemId, $user);

            if ($item->version !== (int) $data['version']) {
                abort(409, 'This item was changed from another device. Refresh and try again.');
            }

            $quantity = (int) $data['quantity'];

            // US-005 AC2: quantity zero removes the line entirely.
            if ($quantity === 0) {
                $item->delete();

                return ['item' => null, 'subtotal' => $this->subtotalFor($participant)];
            }

            // Modifiers omitted = keep the current selection; sent = replace it.
            $modifiers = array_key_exists('modifiers', $data)
                ? $this->resolveModifiers($item->dish, $data['modifiers'] ?? [])
                : ($item->modifiers ?? []);

            $unitPrice = round($item->dish->price + array_sum(array_column($modifiers, 'price')), 2);

            $item->update([
                'quantity' => $quantity,
                'modifiers' => $modifiers === [] ? null : $modifiers,
                'special_instructions' => array_key_exists('special_instructions', $data)
                    ? $data['special_instructions']
                    : $item->special_instructions,
                'unit_price' => $unitPrice,
                'total_price' => round($unitPrice * $quantity, 2),
                'version' => $item->version + 1,
            ]);

            return ['item' => $item, 'subtotal' => $this->subtotalFor($participant)];
        });
    }

    /**
     * US-005 AC3: remove a line from the sub-cart.
     *
     * @return array{subtotal: float}
     */
    public function removeItem(int $groupOrderId, int $itemId, User $user): array
    {
        return DB::transaction(function () use ($groupOrderId, $itemId, $user) {
            [, $participant, $item] = $this->findEditableItem($groupOrderId, $itemId, $user);

            $item->delete();

            return ['subtotal' => $this->subtotalFor($participant)];
        });
    }

    /**
     * Shared guards for edit/remove: the group is editable and the line
     * belongs to the caller — or the caller is the leader, who may edit any
     * sub-cart (spec §2). Returns the item's owning participant so subtotals
     * reflect the owner, not the editor.
     *
     * @return array{0: GroupOrder, 1: GroupParticipant, 2: GroupCartItem}
     */
    private function findEditableItem(int $groupOrderId, int $itemId, User $user): array
    {
        $groupOrder = GroupOrder::query()->whereKey($groupOrderId)->lockForUpdate()->first();

        if ($groupOrder === null) {
            abort(404, 'Group order not found.');
        }

        $item = $groupOrder->cartItems()->with('dish')->find($itemId);

        if ($item === null) {
            abort(404, 'Cart item not found.');
        }

        $owner = $item->participant;
        $isOwn = $owner->user_id === $user->id
            && $owner->status === GroupParticipant::STATUS_JOINED;

        if (! $isOwn && $groupOrder->leader_id !== $user->id) {
            abort(403, 'You can only change items in your own sub-cart.');
        }

        $this->assertEditable($groupOrder);

        return [$groupOrder, $owner, $item];
    }

    /**
     * FR-016 / US-005 AC4: no cart changes once the leader has submitted —
     * and none on cancelled/expired sessions either.
     */
    public function assertEditable(GroupOrder $groupOrder): void
    {
        if ($groupOrder->status === GroupOrder::STATUS_SUBMITTED) {
            abort(409, 'Order has been submitted and cannot be modified.');
        }

        if ($groupOrder->status !== GroupOrder::STATUS_ACTIVE) {
            abort(409, 'This group order is no longer active.');
        }
    }

    /** Running subtotal of a participant's sub-cart (US-004 AC4). */
    public function subtotalFor(GroupParticipant $participant): float
    {
        return round((float) $participant->cartItems()->sum('total_price'), 2);
    }

    /**
     * Validates the selected option ids against the dish's applicable options
     * and snapshots name + price for the cart line (US-004 AC2).
     *
     * @param  list<int>  $optionIds
     * @return list<array{id: int, name: string, price: float}>
     */
    private function resolveModifiers(Dish $dish, array $optionIds): array
    {
        if ($optionIds === []) {
            return [];
        }

        $optionIds = array_values(array_unique(array_map(intval(...), $optionIds)));

        $options = DishOption::query()
            ->whereIn('id', $optionIds)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->whereIn('id', AppliedDishOption::query()
                ->where('dish_id', $dish->id)
                ->select('dish_option_id'))
            ->get();

        if ($options->count() !== count($optionIds)) {
            abort(422, 'One or more selected options are not available for this dish.');
        }

        return $options
            ->map(fn (DishOption $option) => [
                'id' => $option->id,
                'name' => $option->en_name,
                'price' => (float) $option->price,
            ])
            ->values()
            ->all();
    }
}
