<?php

namespace App\Console\Commands;

use App\Services\GroupOrderService;
use Illuminate\Console\Command;

/**
 * FR-005 / US-001 AC5: sweep ACTIVE group orders whose 5-minute window
 * elapsed with nobody joining, mark them EXPIRED and notify the leader.
 * Runs every minute via the scheduler; lazy expiry on reads covers the
 * gap between runs.
 */
class ExpireGroupOrders extends Command
{
    protected $signature = 'group-orders:expire';

    protected $description = 'Expire active group orders whose join window elapsed with no participants';

    public function handle(GroupOrderService $groupOrders): int
    {
        $expired = $groupOrders->expireStale();

        $this->info("Expired {$expired} group order(s).");

        return self::SUCCESS;
    }
}
