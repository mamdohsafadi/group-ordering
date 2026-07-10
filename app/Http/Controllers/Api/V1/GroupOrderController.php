<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JoinGroupOrderRequest;
use App\Http\Requests\StoreGroupOrderRequest;
use App\Http\Resources\GroupOrderResource;
use App\Models\GroupOrder;
use App\Services\GroupOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * US-001/US-002 lifecycle endpoints (spec §8.1/§8.2 + handoff §2).
 * Thin by convention: validate → GroupOrderService → Resource.
 */
class GroupOrderController extends Controller
{
    public function __construct(
        private readonly GroupOrderService $groupOrders,
    ) {}

    /** POST /api/v1/group-orders — create a session (US-001). */
    public function store(StoreGroupOrderRequest $request): JsonResponse
    {
        $groupOrder = $this->groupOrders->create($request->user(), $request->validated());

        return response()->json([
            'group_order_id' => $groupOrder->id,
            'shareable_link' => $groupOrder->shareable_link,
            'expires_at' => $groupOrder->expires_at->toISOString(),
            // Contract quirk (handoff §2.1): the create response reports
            // CREATED even though the stored row is already ACTIVE.
            'status' => GroupOrder::STATUS_CREATED,
        ], 201);
    }

    /** GET /api/v1/group-orders/{id} — lobby state for members. */
    public function show(Request $request, int $groupOrder): GroupOrderResource
    {
        return new GroupOrderResource(
            $this->groupOrders->findForUser($groupOrder, $request->user()),
        );
    }

    /** GET /api/v1/group-orders/by-token/{token} — invite preview (US-002 AC5). */
    public function showByToken(string $token): GroupOrderResource
    {
        return new GroupOrderResource($this->groupOrders->findByToken($token));
    }

    /** POST /api/v1/group-orders/{id}/join — join via link (US-002). */
    public function join(JoinGroupOrderRequest $request, int $groupOrder): JsonResponse
    {
        $result = $this->groupOrders->join(
            $groupOrder,
            $request->validated('link_token'),
            $request->user(),
        );

        return response()->json([
            'participant_id' => $result['participant']->id,
            'group_order_id' => $result['participant']->group_order_id,
            'status' => $result['participant']->status,
            'joined_at' => $result['participant']->joined_at?->toISOString(),
        ], $result['created'] ? 201 : 200);
    }

    /** POST /api/v1/group-orders/{id}/cancel — leader cancels (BR-012). */
    public function cancel(Request $request, int $groupOrder): JsonResponse
    {
        $cancelled = $this->groupOrders->cancel($groupOrder, $request->user());

        return response()->json([
            'success' => true,
            'status' => $cancelled->status,
        ]);
    }
}
