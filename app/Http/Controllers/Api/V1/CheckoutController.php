<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** US-007 checkout (spec §8.8). */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
    ) {}

    /** POST /api/v1/group-orders/{id}/checkout — leader submits the unified order. */
    public function store(Request $request, int $groupOrder): JsonResponse
    {
        $validated = $request->validate([
            'promo_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            // COD for now: accepted for contract compatibility, not used.
            'payment_method_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $result = $this->checkout->submit($groupOrder, $request->user(), $validated['promo_code'] ?? null);

        return response()->json([
            'order_id' => $result['billId'],
            'status' => 'CONFIRMED',
            'total_charged' => $result['totalCharged'],
            'participant_breakdown' => $result['breakdown'],
        ]);
    }
}
