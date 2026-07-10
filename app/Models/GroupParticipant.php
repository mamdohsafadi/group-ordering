<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * A user's membership within a single group order.
 *
 * @property int $id
 * @property int $group_order_id
 * @property int $user_id
 * @property string $status
 * @property Carbon|null $joined_at
 * @property Carbon|null $left_at
 */
class GroupParticipant extends Model
{
    public const STATUS_JOINED = 'JOINED';

    public const STATUS_LEFT = 'LEFT';

    public $timestamps = false;

    protected $fillable = [
        'group_order_id',
        'user_id',
        'joined_at',
        'left_at',
        'status',
    ];

    protected $casts = [
        'group_order_id' => 'int',
        'user_id' => 'int',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    /** The group order this membership belongs to. */
    public function groupOrder(): BelongsTo
    {
        return $this->belongsTo(GroupOrder::class, 'group_order_id');
    }

    /** The underlying user account. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** This participant's sub-cart lines. */
    public function cartItems(): HasMany
    {
        return $this->hasMany(GroupCartItem::class, 'participant_id');
    }

    /** This participant's individual invoice. */
    public function invoice(): HasOne
    {
        return $this->hasOne(GroupInvoice::class, 'participant_id');
    }
}
