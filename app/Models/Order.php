<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A single line item on a bill. Each participant sub-cart line becomes one
 * order row when the group order is submitted.
 *
 * @property int $id
 * @property int $bill_id
 * @property int $dish_id
 * @property int $quantity
 * @property float|null $dish_price
 * @property float|null $total
 * @property float $discount
 * @property string|null $special_instructions
 */
class Order extends Model
{
    protected $table = 'order';

    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'dish_id',
        'quantity',
        'dish_price',
        'total',
        'discount',
        'special_instructions',
    ];

    protected $casts = [
        'bill_id' => 'int',
        'dish_id' => 'int',
        'quantity' => 'int',
        'dish_price' => 'float',
        'total' => 'float',
        'discount' => 'float',
    ];

    /** The bill this line belongs to. */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    /** The menu item ordered. */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id');
    }

    /** The group sub-cart line this order row was created from, if any. */
    public function groupCartItem(): HasOne
    {
        return $this->hasOne(GroupCartItem::class, 'order_id');
    }
}
