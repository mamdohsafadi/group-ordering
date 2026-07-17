<?php

namespace App\Adapters;

use App\Contracts\NotificationDispatcher;
use App\Models\DemoNotification;
use Illuminate\Support\Facades\Log;

/**
 * Demo implementation: notifications land in demo_notifications so the
 * frontend bell can show them (US-003), and in the log for good measure.
 * In production this is replaced by the live app's push system.
 */
class DatabaseNotificationDispatcher implements NotificationDispatcher
{
    public function notify(array $userIds, string $type, array $payload = []): void
    {
        foreach ($userIds as $userId) {
            DemoNotification::create([
                'user_id' => $userId,
                'type' => $type,
                'payload' => $payload,
            ]);
        }

        Log::info("notification: {$type}", ['user_ids' => $userIds, 'payload' => $payload]);
    }
}
