<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog: a single modifier/option a dish can carry.
 *
 * @property int    $id
 * @property int    $dish_group_id
 * @property string $en_name
 * @property string $ar_name
 * @property float  $price
 * @property float  $purchase_price
 * @property bool   $is_default
 * @property bool   $is_active
 * @property bool   $is_deleted
 */
class DishOption extends Model
{
    protected $table = 'dish_options';

    public $timestamps = false;

    protected $fillable = [
        'dish_group_id',
        'en_name',
        'ar_name',
        'price',
        'purchase_price',
        'is_default',
        'is_active',
        'is_deleted',
        'created_at',
    ];

    protected $casts = [
        'dish_group_id' => 'int',
        'price' => 'float',
        'purchase_price' => 'float',
        'is_default' => 'bool',
        'is_active' => 'bool',
        'is_deleted' => 'bool',
        'created_at' => 'datetime',
    ];

    /** The group this option belongs to. */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DishOptionGroup::class, 'dish_group_id');
    }

    /** Applied-option rows referencing this option. */
    public function appliedDishOptions(): HasMany
    {
        return $this->hasMany(AppliedDishOption::class, 'dish_option_id');
    }
}
