<?php

namespace App\Adapters;

use App\Contracts\OrderSubmitter;
use App\DTOs\OrderSubmission;
use App\DTOs\OrderSubmissionResult;
use App\Models\Bill;
use App\Models\GroupOrder;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Demo implementation of the checkout write-path: writes Bill + Order rows
 * into the local stand-in tables. In production this class is replaced by an
 * HTTP client calling the live app's internal order-creation endpoint — this
 * implementation doubles as the behavioural spec for that endpoint.
 */
class LocalOrderSubmitter implements OrderSubmitter
{
    public function submit(OrderSubmission $submission): OrderSubmissionResult
    {
        return DB::transaction(function () use ($submission) {
            // Idempotency (keyed on the group order): if a bill already
            // exists for this group order, return it instead of duplicating.
            $groupOrder = GroupOrder::query()
                ->whereKey($submission->groupOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($groupOrder->bill_id !== null) {
                return new OrderSubmissionResult(
                    billId: $groupOrder->bill_id,
                    orderIds: Order::query()
                        ->where('bill_id', $groupOrder->bill_id)
                        ->pluck('id')
                        ->all(),
                );
            }

            $bill = Bill::create([
                'user_id' => $submission->payerUserId,
                'restaurant_id' => $submission->restaurantId,
                'address_id' => $submission->addressId,
                'time_type' => $submission->timeType,
                'sub_total' => $submission->subTotal,
                'discount' => $submission->discount,
                'delivery' => $submission->deliveryFee,
                'tax' => $submission->tax,
                'net_total' => $submission->netTotal,
                'voucher_id' => $submission->voucherId,
                'open_time' => $submission->scheduledTime,
            ]);

            $orderIds = [];
            foreach ($submission->lines as $line) {
                $orderIds[] = Order::create([
                    'bill_id' => $bill->id,
                    'dish_id' => $line->dishId,
                    'quantity' => $line->quantity,
                    'dish_price' => $line->unitPrice,
                    'total' => $line->totalPrice,
                    'special_instructions' => $line->specialInstructions,
                ])->id;
            }

            return new OrderSubmissionResult(billId: $bill->id, orderIds: $orderIds);
        });
    }
}
