<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A discount/promo code applied to an order.
 *
 * @property int      $id
 * @property string   $voucher
 * @property int|null $category
 * @property int      $type
 * @property int      $apply_on
 * @property float    $value
 * @property int|null $bill_id
 * @property int|null $dish_id
 * @property float    $total
 */
class Voucher extends Model
{
    protected $table = 'vouchers';

    public $timestamps = false;

    protected $fillable = [
        'voucher',
        'category',
        'type',
        'apply_on',
        'value',
        'bill_id',
        'dish_id',
        'total',
    ];

    protected $casts = [
        'category' => 'int',
        'type' => 'int',
        'apply_on' => 'int',
        'value' => 'float',
        'bill_id' => 'int',
        'dish_id' => 'int',
        'total' => 'float',
    ];

    /** The bill this voucher was applied to (if any). */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
