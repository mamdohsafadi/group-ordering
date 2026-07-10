<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu item that a participant can add to their sub-cart.
 *
 * @property int $id
 * @property int $restaurant_id
 * @property string $name
 * @property string|null $eng_name
 * @property float|null $price
 * @property int $active
 */
class Dish extends Model
{
    protected $table = 'dish';

    public $timestamps = false;

    protected $fillable = [
        'restaurant_id',
        'name',
        'eng_name',
        'price',
        'active',
    ];

    protected $casts = [
        'restaurant_id' => 'int',
        'price' => 'float',
        'active' => 'int',
    ];

    /** Restaurant this menu item belongs to. */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /** Modifiers applied to this dish. */
    public function appliedOptions(): HasMany
    {
        return $this->hasMany(AppliedDishOption::class, 'dish_id');
    }
}
