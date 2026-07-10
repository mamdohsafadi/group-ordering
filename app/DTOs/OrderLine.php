<?php

namespace App\DTOs;

/**
 * One merged order line handed to the live system at checkout.
 * Carries the participant it came from so the live side can keep attribution.
 */
final readonly class OrderLine
{
    public function __construct(
        public int $dishId,
        public int $participantUserId,
        public int $quantity,
        public float $unitPrice,
        public float $totalPrice,
        public ?array $modifiers = null,
        public ?string $specialInstructions = null,
    ) {}
}
