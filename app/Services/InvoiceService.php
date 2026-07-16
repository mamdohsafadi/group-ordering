<?php

namespace App\Services;

use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;

/**
 * Builds the per-participant and master invoice views (US-006, FR-018..FR-023).
 * Before submission the figures are a live preview (discount lands at
 * checkout); after submission they come from the stored group_invoices rows,
 * which are the financial record.
 */
class InvoiceService
{
    /**
     * Demo stand-ins: fee and tax are owned by the live app and will come
     * from its data at adoption. Flat fee, tax as a rate on the subtotal.
     */
    public const DELIVERY_FEE = 10.00;

    public const TAX_RATE = 0.05;

    public function __construct(
        private readonly GroupOrderService $groupOrders,
        private readonly InvoiceCalculator $calculator,
    ) {}

    /** §8.6 — the caller's own invoice. */
    public function forParticipant(int $groupOrderId, User $user): array
    {
        $groupOrder = $this->groupOrders->findForUser($groupOrderId, $user);

        $me = $groupOrder->participants->first(
            fn ($p) => $p->user_id === $user->id && $p->status === GroupParticipant::STATUS_JOINED,
        );

        if ($me === null) {
            abort(403, 'You are not part of this group order.');
        }

        $shares = $this->shares($groupOrder);

        return [
            'items' => $this->items($me),
            ...$shares[$me->id],
        ];
    }

    /** §8.7 — every participant's invoice, leader only (US-006 AC3). */
    public function master(int $groupOrderId, User $user): array
    {
        $groupOrder = $this->groupOrders->findForUser($groupOrderId, $user);

        if ($groupOrder->leader_id !== $user->id) {
            abort(403, 'Only the group leader can view the master invoice.');
        }

        $shares = $this->shares($groupOrder);
        $joined = $groupOrder->participants
            ->filter(fn ($p) => $p->status === GroupParticipant::STATUS_JOINED)
            ->sortBy('id')
            ->values();

        $participants = $joined->map(fn ($p) => [
            'participant_id' => $p->id,
            'user_id' => $p->user_id,
            'name' => $p->user->name,
            'items' => $this->items($p),
            ...$shares[$p->id],
        ]);

        $grandTotal = round($participants->sum('subtotal'), 2);
        $discount = round(-$participants->sum('discount_share'), 2);

        return [
            'participants' => $participants,
            'grand_total' => $grandTotal,
            'delivery_fee' => $groupOrder->status === GroupOrder::STATUS_SUBMITTED
                ? round((float) $groupOrder->bill?->delivery, 2)
                : self::DELIVERY_FEE,
            'tax' => round($participants->sum('tax_share'), 2),
            'discount' => $discount,
            'total' => round($participants->sum('total'), 2),
        ];
    }

    /**
     * Per-participant share rows keyed by participant id. Stored invoices win
     * once submitted; otherwise a live preview via the calculator.
     *
     * @return array<int, array{subtotal: float, delivery_fee_share: float, tax_share: float, discount_share: float, total: float}>
     */
    public function shares(GroupOrder $groupOrder, float $discount = 0.0): array
    {
        if ($groupOrder->status === GroupOrder::STATUS_SUBMITTED) {
            return $groupOrder->invoices()
                ->where('is_master', false)
                ->get()
                ->keyBy('participant_id')
                ->map(fn ($invoice) => [
                    'subtotal' => $invoice->subtotal,
                    'delivery_fee_share' => $invoice->delivery_fee_share,
                    'tax_share' => $invoice->tax_share,
                    'discount_share' => $invoice->discount_share,
                    'total' => $invoice->total,
                ])
                ->all();
        }

        $joined = $groupOrder->participants
            ->filter(fn ($p) => $p->status === GroupParticipant::STATUS_JOINED);

        $subtotals = $joined
            ->mapWithKeys(fn ($p) => [$p->id => round((float) $p->cartItems->sum('total_price'), 2)])
            ->all();

        $totalSubtotal = array_sum($subtotals);

        return $this->calculator->split(
            $subtotals,
            self::DELIVERY_FEE,
            round($totalSubtotal * self::TAX_RATE, 2),
            $discount,
        );
    }

    /** Itemised lines for one participant (US-006 AC1). */
    private function items(GroupParticipant $participant): array
    {
        return $participant->cartItems
            ->sortBy('id')
            ->values()
            ->map(fn ($item) => [
                'id' => $item->id,
                'dish_name' => $item->dish->eng_name,
                'quantity' => $item->quantity,
                'modifiers' => $item->modifiers,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
            ])
            ->all();
    }
}
