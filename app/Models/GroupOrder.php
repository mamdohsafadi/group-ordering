<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A collaborative ordering session led by one user.
 *
 * @property int         $id
 * @property int         $leader_id
 * @property int         $restaurant_id
 * @property int|null    $delivery_address_id
 * @property int|null    $bill_id
 * @property string      $status
 * @property string      $shareable_link
 * @property string|null $promo_code
 * @property string      $delivery_time_type
 * @property \Illuminate\Support\Carbon|null $scheduled_time
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $submitted_at
 */
class GroupOrder extends Model
{
    // status values
    public const STATUS_CREATED = 'CREATED';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_EXPIRED = 'EXPIRED';

    // delivery_time_type values
    public const DELIVERY_ASAP = 'ASAP';
    public const DELIVERY_SCHEDULED = 'SCHEDULED';

    protected $fillable = [
        'leader_id',
        'restaurant_id',
        'delivery_address_id',
        'bill_id',
        'status',
        'shareable_link',
        'promo_code',
        'delivery_time_type',
        'scheduled_time',
        'expires_at',
        'submitted_at',
    ];

    protected $casts = [
        'leader_id' => 'int',
        'restaurant_id' => 'int',
        'delivery_address_id' => 'int',
        'bill_id' => 'int',
        'scheduled_time' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /** The user who owns and manages this group order. */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /** The restaurant every sub-cart is ordered from. */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    /** The delivery address chosen by the leader. */
    public function deliveryAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'delivery_address_id');
    }

    /** The unified bill produced at checkout (null until submitted). */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }

    /** All participants (including any who have left). */
    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class, 'group_order_id');
    }

    /** Every sub-cart line across all participants. */
    public function cartItems(): HasMany
    {
        return $this->hasMany(GroupCartItem::class, 'group_order_id');
    }

    /** Individual invoices plus the master invoice. */
    public function invoices(): HasMany
    {
        return $this->hasMany(GroupInvoice::class, 'group_order_id');
    }

    /** The leader's consolidated master invoice. */
    public function masterInvoice(): HasOne
    {
        return $this->hasOne(GroupInvoice::class, 'group_order_id')->where('is_master', true);
    }
}
