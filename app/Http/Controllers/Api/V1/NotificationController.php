<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DemoNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Demo in-app notification inbox (US-003) — stand-in for the live push
 * system; the notification *dispatch* side is the durable part, this feed
 * exists so the showcase can receive what the seam sends.
 */
class NotificationController extends Controller
{
    /** GET /api/v1/notifications — the caller's latest notifications. */
    public function index(Request $request): JsonResponse
    {
        $notifications = DemoNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->limit(20)
            ->get(['id', 'type', 'payload', 'read_at', 'created_at']);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    /** POST /api/v1/notifications/read — mark everything read. */
    public function markRead(Request $request): JsonResponse
    {
        DemoNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
