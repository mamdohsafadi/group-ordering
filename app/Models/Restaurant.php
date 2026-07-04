<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Restaurant a group order is placed against.
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $arabic_name
 * @property int         $active
 */
class Restaurant extends Model
{
    protected $table = 'restaurant';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'arabic_name',
        'active',
    ];

    protected $casts = [
        'active' => 'int',
    ];

    /** Menu items belonging to this restaurant. */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class, 'restaurant_id');
    }

    /** Group orders opened for this restaurant. */
    public function groupOrders(): HasMany
    {
        return $this->hasMany(GroupOrder::class, 'restaurant_id');
    }
}
