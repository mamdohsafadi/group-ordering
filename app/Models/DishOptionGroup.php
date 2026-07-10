<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog: a group of modifiers on a restaurant's menu (e.g. "Size").
 *
 * @property int $id
 * @property int $restaurant_id
 * @property string $en_name
 * @property string $ar_name
 * @property bool $is_active
 * @property bool $is_deleted
 */
class DishOptionGroup extends Model
{
    protected $table = 'dish_options_group';

    public $timestamps = false;

    protected $fillable = [
        'restaurant_id',
        'en_name',
        'ar_name',
        'is_active',
        'is_deleted',
        'created_at',
    ];

    protected $casts = [
        'restaurant_id' => 'int',
        'is_active' => 'bool',
        'is_deleted' => 'bool',
        'created_at' => 'datetime',
    ];

    /** The restaurant this group belongs to. */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /** Options within this group. */
    public function options(): HasMany
    {
        return $this->hasMany(DishOption::class, 'dish_group_id');
    }
}
