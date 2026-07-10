<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery address selected by the group leader at checkout.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name
 * @property string|null $street
 * @property string|null $details
 * @property float|null $latitude
 * @property float|null $longitude
 */
class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_address';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'street',
        'details',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'user_id' => 'int',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /** Owner of this address. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
