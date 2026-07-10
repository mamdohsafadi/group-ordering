<?php

namespace App\Adapters;

use App\Contracts\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Demo implementation: records notifications in the log (visible via pail).
 * In production this is replaced by the live app's push-notification system.
 * Broadcast-based delivery for the demo lobby can wrap or replace this.
 */
class LogNotificationDispatcher implements NotificationDispatcher
{
    public function notify(array $userIds, string $type, array $payload = []): void
    {
        Log::info("notification: {$type}", [
            'user_ids' => $userIds,
            'payload' => $payload,
        ]);
    }
}
