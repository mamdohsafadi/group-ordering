<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-participant cost breakdown, or the leader's consolidated master invoice
 * when $is_master is true (in which case participant_id is null).
 *
 * @property int        $id
 * @property int        $group_order_id
 * @property int|null   $participant_id
 * @property float      $subtotal
 * @property float      $delivery_fee_share
 * @property float      $tax_share
 * @property float      $discount_share
 * @property float      $total
 * @property bool       $is_master
 */
class GroupInvoice extends Model
{
    protected $fillable = [
        'group_order_id',
        'participant_id',
        'subtotal',
        'delivery_fee_share',
        'tax_share',
        'discount_share',
        'total',
        'is_master',
    ];

    protected $casts = [
        'group_order_id' => 'int',
        'participant_id' => 'int',
        'subtotal' => 'float',
        'delivery_fee_share' => 'float',
        'tax_share' => 'float',
        'discount_share' => 'float',
        'total' => 'float',
        'is_master' => 'bool',
    ];

    /** The group order this invoice belongs to. */
    public function groupOrder(): BelongsTo
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    /** The participant this invoice is for (null for the master invoice). */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(GroupParticipant::class, 'participant_id');
    }
}
