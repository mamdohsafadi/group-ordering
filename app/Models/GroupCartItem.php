<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line item in a participant's sub-cart.
 *
 * @property int         $id
 * @property int         $group_order_id
 * @property int         $participant_id
 * @property int         $dish_id
 * @property int|null    $order_id
 * @property int         $quantity
 * @property array|null  $modifiers
 * @property string|null $special_instructions
 * @property float       $unit_price
 * @property float       $total_price
 * @property int         $version
 */
class GroupCartItem extends Model
{
    protected $fillable = [
        'group_order_id',
        'participant_id',
        'dish_id',
        'order_id',
        'quantity',
        'modifiers',
        'special_instructions',
        'unit_price',
        'total_price',
        'version',
    ];

    protected $casts = [
        'group_order_id' => 'int',
        'participant_id' => 'int',
        'dish_id' => 'int',
        'order_id' => 'int',
        'quantity' => 'int',
        'modifiers' => 'array',
        'unit_price' => 'float',
        'total_price' => 'float',
        'version' => 'int',
    ];

    /** The group order this line belongs to. */
    public function groupOrder(): BelongsTo
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    /** The participant who added this line. */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(GroupParticipant::class, 'participant_id');
    }

    /** The menu item this line refers to. */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id');
    }

    /** The order row this line became at checkout (null until submitted). */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
