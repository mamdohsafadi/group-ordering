<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Pure equal-split invoice maths (FR-018..FR-022, BR-006).
 *
 * Delivery fee, tax and discount are split equally among all participants
 * including the leader. All arithmetic happens in integer cents (NFR-007);
 * remainder cents are assigned deterministically to the earliest participants
 * so the shares always sum exactly to the group totals.
 */
class InvoiceCalculator
{
    /**
     * Returns invoices keyed by participant_id; discount_share is negative or zero.
     *
     * @param  array<int, float>  $subtotalsByParticipant  participant_id => sub-cart subtotal
     * @return array<int, array{subtotal: float, delivery_fee_share: float, tax_share: float, discount_share: float, total: float}>
     */
    public function split(array $subtotalsByParticipant, float $deliveryFee, float $tax, float $discount): array
    {
        $count = count($subtotalsByParticipant);

        if ($count === 0) {
            throw new InvalidArgumentException('Cannot split an invoice across zero participants.');
        }

        $deliveryShares = $this->splitCentsEqually($this->toCents($deliveryFee), $count);
        $taxShares = $this->splitCentsEqually($this->toCents($tax), $count);
        $discountShares = $this->splitCentsEqually($this->toCents($discount), $count);

        $invoices = [];
        $index = 0;

        foreach ($subtotalsByParticipant as $participantId => $subtotal) {
            $subtotalCents = $this->toCents($subtotal);
            $totalCents = $subtotalCents
                + $deliveryShares[$index]
                + $taxShares[$index]
                - $discountShares[$index];

            $invoices[$participantId] = [
                'subtotal' => $this->toFloat($subtotalCents),
                'delivery_fee_share' => $this->toFloat($deliveryShares[$index]),
                'tax_share' => $this->toFloat($taxShares[$index]),
                'discount_share' => $this->toFloat(-$discountShares[$index]),
                'total' => $this->toFloat($totalCents),
            ];

            $index++;
        }

        return $invoices;
    }

    /**
     * Splits an amount of cents into $count equal shares; the remainder is
     * distributed one cent at a time to the earliest shares.
     *
     * @return list<int>
     */
    private function splitCentsEqually(int $cents, int $count): array
    {
        $base = intdiv($cents, $count);
        $remainder = $cents % $count;

        $shares = array_fill(0, $count, $base);

        for ($i = 0; $i < $remainder; $i++) {
            $shares[$i]++;
        }

        return $shares;
    }

    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function toFloat(int $cents): float
    {
        return $cents / 100;
    }
}
