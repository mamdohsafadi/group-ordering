<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A modifier selected for a dish — the applied options of an order line.
 * dish_id references `dish` (not the order row).
 *
 * @property int $id
 * @property int $dish_id
 * @property int $dish_option_id
 */
class AppliedDishOption extends Model
{
    protected $table = 'applied_dish_options';

    public $timestamps = false;

    protected $fillable = [
        'dish_id',
        'dish_option_id',
    ];

    protected $casts = [
        'dish_id' => 'int',
        'dish_option_id' => 'int',
    ];

    /** The dish this option was applied to. */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class, 'dish_id');
    }

    /** The selected option from the catalog. */
    public function dishOption(): BelongsTo
    {
        return $this->belongsTo(DishOption::class, 'dish_option_id');
    }
}
