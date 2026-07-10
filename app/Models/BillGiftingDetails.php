<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Optional gifting metadata for a bill.
 *
 * @property int $id
 * @property int $bill_id
 * @property string|null $recipient_name
 * @property string|null $recipient_phone
 */
class BillGiftingDetails extends Model
{
    protected $table = 'bill_gifting_details';

    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'recipient_name',
        'recipient_phone',
    ];

    protected $casts = [
        'bill_id' => 'int',
    ];

    /** The bill this gifting detail belongs to. */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
