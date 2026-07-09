<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The real order/checkout record sent to the restaurant. A group order
 * produces exactly one bill at checkout, charged to the leader.
 *
 * @property int $id
 * @property int $user_id
 * @property int $restaurant_id
 * @property int|null $address_id
 * @property int $bill_type
 * @property string $time_type
 * @property int $stage
 * @property int|null $state
 * @property float|null $sub_total
 * @property float $discount
 * @property float $delivery
 * @property float|null $tax
 * @property float|null $net_total
 * @property int|null $voucher_id
 */
class Bill extends Model
{
    protected $table = 'bill';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'address_id',
        'bill_type',
        'time_type',
        'stage',
        'state',
        'sub_total',
        'discount',
        'delivery',
        'tax',
        'net_total',
        'voucher_id',
        'open_time',
    ];

    protected $casts = [
        'user_id' => 'int',
        'restaurant_id' => 'int',
        'address_id' => 'int',
        'bill_type' => 'int',
        'stage' => 'int',
        'state' => 'int',
        'sub_total' => 'float',
        'discount' => 'float',
        'delivery' => 'float',
        'tax' => 'float',
        'net_total' => 'float',
        'voucher_id' => 'int',
        'open_time' => 'datetime',
    ];

    /** The payer (group leader). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The restaurant fulfilling this bill. */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /** The delivery address. */
    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    /** Line items on this bill. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'bill_id');
    }

    /** Bill type lookup (PICKUP / DELIVERY). */
    public function billType(): BelongsTo
    {
        return $this->belongsTo(BillType::class, 'bill_type');
    }

    /** Bill stage lookup (PENDING / PAID / ...). */
    public function billStage(): BelongsTo
    {
        return $this->belongsTo(BillStage::class, 'stage');
    }

    /** Per-bill tax breakdown (distinct from the aggregated `tax` column). */
    public function taxBreakdown(): HasOne
    {
        return $this->hasOne(BillTax::class, 'bill_id');
    }

    /** Service lines charged on this bill. */
    public function services(): HasMany
    {
        return $this->hasMany(BillServices::class, 'bill_id');
    }

    /** Optional gifting details. */
    public function giftingDetails(): HasOne
    {
        return $this->hasOne(BillGiftingDetails::class, 'bill_id');
    }

    /** The discount/voucher applied to this bill, if any. */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /** The group order this bill originated from, if any. */
    public function groupOrder(): HasOne
    {
        return $this->hasOne(GroupOrder::class, 'bill_id');
    }
}
