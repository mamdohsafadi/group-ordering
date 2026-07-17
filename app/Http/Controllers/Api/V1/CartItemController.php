<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sub-cart endpoints for US-004/US-005 (spec §8.3–§8.5).
 * Thin by convention: validate → CartService → contract response.
 */
class CartItemController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
    ) {}

    /** POST /api/v1/group-orders/{id}/cart/items — add to own sub-cart (US-004). */
    public function store(StoreCartItemRequest $request, int $groupOrder): JsonResponse
    {
        $result = $this->cart->addItem($groupOrder, $request->user(), $request->validated());

        return response()->json([
            'cart_item_id' => $result['item']->id,
            'total_price' => $result['item']->total_price,
            'updated_subtotal' => $result['subtotal'],
        ], 201);
    }

    /** PUT /api/v1/group-orders/{id}/cart/items/{item} — edit quantity/modifiers (US-005). */
    public function update(UpdateCartItemRequest $request, int $groupOrder, int $item): JsonResponse
    {
        $result = $this->cart->updateItem($groupOrder, $item, $request->user(), $request->validated());

        return response()->json([
            'cart_item_id' => $item,
            // Quantity 0 removed the line (US-005 AC2) — nothing left to price.
            'total_price' => $result['item']?->total_price ?? 0,
            'updated_subtotal' => $result['subtotal'],
        ]);
    }

    /** DELETE /api/v1/group-orders/{id}/cart/items/{item} — remove a line (US-005). */
    public function destroy(Request $request, int $groupOrder, int $item): JsonResponse
    {
        $result = $this->cart->removeItem($groupOrder, $item, $request->user());

        return response()->json([
            'success' => true,
            'updated_subtotal' => $result['subtotal'],
        ]);
    }
}
