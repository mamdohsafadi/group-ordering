<?php

namespace Tests\Unit;

use App\Services\InvoiceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InvoiceCalculatorTest extends TestCase
{
    private InvoiceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new InvoiceCalculator;
    }

    public function test_splits_fees_equally_and_assigns_remainder_cents_to_earliest_participants(): void
    {
        $invoices = $this->calculator->split(
            subtotalsByParticipant: [1 => 30.00, 2 => 20.00, 3 => 10.00],
            deliveryFee: 10.00,
            tax: 5.00,
            discount: 0.00,
        );

        // 1000 cents / 3 = 333 remainder 1 -> first participant gets the extra cent.
        $this->assertSame(3.34, $invoices[1]['delivery_fee_share']);
        $this->assertSame(3.33, $invoices[2]['delivery_fee_share']);
        $this->assertSame(3.33, $invoices[3]['delivery_fee_share']);

        // 500 cents / 3 = 166 remainder 2 -> first two participants get an extra cent.
        $this->assertSame(1.67, $invoices[1]['tax_share']);
        $this->assertSame(1.67, $invoices[2]['tax_share']);
        $this->assertSame(1.66, $invoices[3]['tax_share']);
    }

    public function test_shares_always_sum_exactly_to_the_group_totals(): void
    {
        $invoices = $this->calculator->split(
            subtotalsByParticipant: [1 => 12.35, 2 => 7.99, 3 => 21.50, 4 => 3.25, 5 => 18.10, 6 => 9.99, 7 => 14.20],
            deliveryFee: 7.77,
            tax: 4.44,
            discount: 10.00,
        );

        $sum = fn (string $key): float => round(array_sum(array_column($invoices, $key)), 2);

        $this->assertSame(7.77, $sum('delivery_fee_share'));
        $this->assertSame(4.44, $sum('tax_share'));
        $this->assertSame(-10.00, $sum('discount_share'));
        $this->assertSame(
            round(12.35 + 7.99 + 21.50 + 3.25 + 18.10 + 9.99 + 14.20 + 7.77 + 4.44 - 10.00, 2),
            $sum('total'),
        );
    }

    public function test_discount_share_is_negative_and_reduces_each_total(): void
    {
        $invoices = $this->calculator->split(
            subtotalsByParticipant: [1 => 20.00, 2 => 20.00],
            deliveryFee: 0.00,
            tax: 0.00,
            discount: 5.00,
        );

        $this->assertSame(-2.50, $invoices[1]['discount_share']);
        $this->assertSame(17.50, $invoices[1]['total']);
        $this->assertSame(17.50, $invoices[2]['total']);
    }

    public function test_single_participant_carries_all_shares(): void
    {
        $invoices = $this->calculator->split(
            subtotalsByParticipant: [9 => 42.00],
            deliveryFee: 3.50,
            tax: 1.25,
            discount: 2.00,
        );

        $this->assertSame(
            ['subtotal' => 42.00, 'delivery_fee_share' => 3.50, 'tax_share' => 1.25, 'discount_share' => -2.00, 'total' => 44.75],
            $invoices[9],
        );
    }

    public function test_rejects_zero_participants(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->split([], 5.00, 1.00, 0.00);
    }
}
