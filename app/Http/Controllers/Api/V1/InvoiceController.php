<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Invoice views for US-006 (spec §8.6/§8.7). */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    /** GET /api/v1/group-orders/{id}/invoice — own breakdown. */
    public function show(Request $request, int $groupOrder): JsonResponse
    {
        return response()->json($this->invoices->forParticipant($groupOrder, $request->user()));
    }

    /** GET /api/v1/group-orders/{id}/invoice/master — leader-only consolidated view. */
    public function master(Request $request, int $groupOrder): JsonResponse
    {
        return response()->json($this->invoices->master($groupOrder, $request->user()));
    }
}
