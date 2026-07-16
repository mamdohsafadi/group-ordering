<?php

namespace App\Http\Resources;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lobby payload for GET /group-orders/{id} and /group-orders/by-token/{token}.
 * This shape IS the frontend contract (backend-api-handoff §2.2) — Lobby.vue
 * and Join.vue consume every field below; never return raw models.
 *
 * `leader_name`/`restaurant_name` are denormalised so the pages need no
 * extra lookups; `is_leader` is derived per participant.
 *
 * @mixin GroupOrder
 */
class GroupOrderResource extends JsonResource
{
    /** Serialise without the default "data" envelope — the pages expect the bare object. */
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'leader_id' => $this->leader_id,
            'leader_name' => $this->leader->name,
            'restaurant_id' => $this->restaurant_id,
            'restaurant_name' => $this->restaurant->name,
            'delivery_address_id' => $this->delivery_address_id,
            'delivery_time_type' => $this->delivery_time_type,
            'scheduled_time' => $this->scheduled_time?->toISOString(),
            'status' => $this->status,
            'shareable_link' => $this->shareable_link,
            'promo_code' => $this->promo_code,
            'created_at' => $this->created_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'participants' => $this->participants
                ->sortBy('id')
                ->values()
                ->map(fn (GroupParticipant $participant) => [
                    'id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'name' => $participant->user->name,
                    'is_leader' => $participant->user_id === $this->leader_id,
                    'status' => $participant->status,
                    'joined_at' => $participant->joined_at?->toISOString(),
                ]),
            'my_cart' => $this->myCart($request),
        ];
    }

    /**
     * The requesting participant's own sub-cart (US-004 AC4). Only the
     * caller's items are exposed — participants never see each other's
     * carts (spec §2); null for non-members previewing via the link.
     *
     * @return array{items: mixed, subtotal: float}|null
     */
    private function myCart(Request $request): ?array
    {
        $me = $this->participants->first(
            fn (GroupParticipant $participant) => $participant->user_id === $request->user()?->id
                && $participant->status === GroupParticipant::STATUS_JOINED,
        );

        if ($me === null) {
            return null;
        }

        return [
            'items' => $me->cartItems
                ->sortBy('id')
                ->values()
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'dish_id' => $item->dish_id,
                    'dish_name' => $item->dish->eng_name,
                    'quantity' => $item->quantity,
                    'modifiers' => $item->modifiers,
                    'special_instructions' => $item->special_instructions,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'version' => $item->version,
                ]),
            'subtotal' => round((float) $me->cartItems->sum('total_price'), 2),
        ];
    }
}
