<?php

namespace App\Contracts;

use App\DTOs\OrderSubmission;
use App\DTOs\OrderSubmissionResult;

/**
 * The single write-path into the live BeeOrder system.
 *
 * Local/demo: LocalOrderSubmitter writes Bill + Order rows into the stand-in
 * tables. Production: an HTTP implementation calling the live app's internal
 * order-creation endpoint. Implementations MUST be idempotent on
 * $submission->groupOrderId — a retry or redelivered message must return the
 * existing result, never create a duplicate order.
 */
interface OrderSubmitter
{
    public function submit(OrderSubmission $submission): OrderSubmissionResult;
}
