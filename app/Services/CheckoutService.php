<?php

namespace App\Services;

use App\Contracts\NotificationDispatcher;
use App\Contracts\OrderSubmitter;
use App\DTOs\OrderLine;
use App\DTOs\OrderSubmission;
use App\Models\GroupOrder;
use App\Models\GroupParticipant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

/**
 * US-007: the leader submits the group order (spec §8.8, FR-017/FR-024..FR-029).
 * Merges every sub-cart into one OrderSubmission and hands it to the
 * OrderSubmitter seam — the only write-path into the live system, idempotent
 * on the group order id. Payment is cash-on-delivery; payment_method_id is
 * accepted for contract compatibility and ignored for now.
 */
class CheckoutService
{
    public function __construct(
        private readonly OrderSubmitter $submitter,
        private readonly InvoiceService $invoices,
        private readonly NotificationDispatcher $notifier,
    ) {}

    /**
     * @return array{groupOrder: GroupOrder, billId: int, totalCharged: float, breakdown: array}
     */
    public function submit(int $groupOrderId, User $user, ?string $promoCode): array
    {
        return DB::transaction(function () use ($groupOrderId, $user, $promoCode) {
            $groupOrder = GroupOrder::query()
                ->with(['participants.user', 'participants.cartItems.dish'])
                ->whereKey($groupOrderId)
                ->lockForUpdate()
                ->first();

            if ($groupOrder === null) {
                abort(404, 'Group order not found.');
            }

            if ($groupOrder->leader_id !== $user->id) {
                abort(403, 'Only the group leader can submit the order.');
            }

            // Idempotency: a retry of an already-submitted order returns the
            // existing result instead of double-submitting.
            if ($groupOrder->status === GroupOrder::STATUS_SUBMITTED) {
                return $this->existingResult($groupOrder);
            }

            if ($groupOrder->status !== GroupOrder::STATUS_ACTIVE) {
                abort(409, 'This group order is no longer active.');
            }

            $joined = $groupOrder->participants
                ->filter(fn ($p) => $p->status === GroupParticipant::STATUS_JOINED);
            $items = $joined->flatMap->cartItems;

            if ($items->isEmpty()) {
                abort(422, 'Cannot submit an empty order. Add items first.');
            }

            $voucher = $this->resolveVoucher($promoCode);
            $discount = $voucher !== null ? round(min((float) $voucher->value, (float) $items->sum('total_price')), 2) : 0.0;

            $shares = $this->invoices->shares($groupOrder, $discount);
            $subTotal = round($items->sum('total_price'), 2);
            $tax = round(array_sum(array_column($shares, 'tax_share')), 2);
            $netTotal = round(array_sum(array_column($shares, 'total')), 2);

            // FR-017/FR-028: one unified order through the seam.
            $result = $this->submitter->submit(new OrderSubmission(
                groupOrderId: $groupOrder->id,
                payerUserId: $groupOrder->leader_id,
                restaurantId: $groupOrder->restaurant_id,
                addressId: $groupOrder->delivery_address_id,
                timeType: $groupOrder->delivery_time_type,
                scheduledTime: $groupOrder->scheduled_time,
                lines: $items->map(fn ($item) => new OrderLine(
                    dishId: $item->dish_id,
                    participantUserId: $item->participant->user_id,
                    quantity: $item->quantity,
                    unitPrice: $item->unit_price,
                    totalPrice: $item->total_price,
                    modifiers: $item->modifiers,
                    specialInstructions: $item->special_instructions,
                ))->values()->all(),
                subTotal: $subTotal,
                deliveryFee: InvoiceService::DELIVERY_FEE,
                tax: $tax,
                discount: $discount,
                netTotal: $netTotal,
                voucherId: $voucher?->id,
            ));

            // Bridge bookkeeping: link each cart line to the order row it became.
            foreach ($items->values() as $index => $item) {
                $item->update(['order_id' => $result->orderIds[$index] ?? null]);
            }

            // FR-022/FR-023: persist the financial record.
            foreach ($shares as $participantId => $share) {
                $groupOrder->invoices()->create([...$share, 'participant_id' => $participantId, 'is_master' => false]);
            }

            $groupOrder->invoices()->create([
                'participant_id' => null,
                'is_master' => true,
                'subtotal' => $subTotal,
                'delivery_fee_share' => InvoiceService::DELIVERY_FEE,
                'tax_share' => $tax,
                'discount_share' => -$discount,
                'total' => $netTotal,
            ]);

            $groupOrder->update([
                'status' => GroupOrder::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'bill_id' => $result->billId,
                'promo_code' => $voucher?->voucher,
            ]);

            // FR-029: everyone gets the confirmation.
            $userIds = $joined->pluck('user_id')->all();
            DB::afterCommit(fn () => $this->notifier->notify($userIds, 'order.confirmed', [
                'group_order_id' => $groupOrder->id,
                'bill_id' => $result->billId,
            ]));

            return [
                'groupOrder' => $groupOrder,
                'billId' => $result->billId,
                'totalCharged' => $netTotal,
                'breakdown' => $this->breakdown($groupOrder, $shares),
            ];
        });
    }

    /** FR-027: promo codes must exist and be unused (spec §10.3 message). */
    private function resolveVoucher(?string $code): ?Voucher
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $voucher = Voucher::query()
            ->where('voucher', trim($code))
            ->whereNull('bill_id')
            ->first();

        if ($voucher === null) {
            abort(422, 'Invalid or expired promo code. Please check and try again.');
        }

        return $voucher;
    }

    /** Idempotent replay: rebuild the response from the stored invoices. */
    private function existingResult(GroupOrder $groupOrder): array
    {
        $master = $groupOrder->invoices()->where('is_master', true)->first();

        return [
            'groupOrder' => $groupOrder,
            'billId' => $groupOrder->bill_id,
            'totalCharged' => $master?->total ?? 0.0,
            'breakdown' => $this->breakdown($groupOrder, $this->invoices->shares($groupOrder)),
        ];
    }

    private function breakdown(GroupOrder $groupOrder, array $shares): array
    {
        return $groupOrder->participants
            ->filter(fn ($p) => isset($shares[$p->id]))
            ->sortBy('id')
            ->values()
            ->map(fn ($p) => ['participant_id' => $p->id, 'name' => $p->user->name, ...$shares[$p->id]])
            ->all();
    }
}
