<?php

namespace App\DTOs;

/**
 * The full payload handed to the live system at checkout (FR-017, FR-028).
 *
 * This shape doubles as the spec for the internal order-creation endpoint the
 * live app will expose at adoption time. $groupOrderId is the idempotency key:
 * submitting the same group order twice must never create a duplicate order.
 */
final readonly class OrderSubmission
{
    /** @param list<OrderLine> $lines */
    public function __construct(
        public int $groupOrderId,
        public int $payerUserId,
        public int $restaurantId,
        public ?int $addressId,
        public string $timeType,
        public ?\DateTimeInterface $scheduledTime,
        public array $lines,
        public float $subTotal,
        public float $deliveryFee,
        public float $tax,
        public float $discount,
        public float $netTotal,
        public ?int $voucherId = null,
    ) {}
}
