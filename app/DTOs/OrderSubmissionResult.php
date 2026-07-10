<?php

namespace App\DTOs;

/**
 * Outcome of handing a merged group order to the live system.
 * $billId is the live system's order reference; store it on the group order.
 */
final readonly class OrderSubmissionResult
{
    public function __construct(
        public int $billId,
        /** @var list<int> IDs of the order lines created, index-aligned with the submitted lines. */
        public array $orderIds,
    ) {}
}
