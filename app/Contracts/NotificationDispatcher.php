<?php

namespace App\Contracts;

/**
 * Sends user-facing notifications (join confirmations, member-left notices,
 * cancellations, order confirmations — FR-012, FR-029, BR-012).
 *
 * Local/demo: logs and/or broadcasts to the demo frontend. Production: the
 * live app's push-notification system, reaching the Flutter app.
 */
interface NotificationDispatcher
{
    /**
     * @param  list<int>  $userIds  recipients (live-app user IDs)
     * @param  string  $type  stable event name, e.g. "participant.left", "order.confirmed"
     * @param  array  $payload  event data; must be JSON-serialisable
     */
    public function notify(array $userIds, string $type, array $payload = []): void;
}
