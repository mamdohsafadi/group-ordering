<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A service line charged on a bill (e.g. packaging).
 *
 * @property int $id
 * @property int $bill_id
 * @property int|null $service_id
 * @property float $service_sub_total
 * @property float $service_sub_total_commission
 * @property float $service_tax
 */
class BillServices extends Model
{
    protected $table = 'bill_services';

    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'service_id',
        'service_sub_total',
        'service_sub_total_commission',
        'service_tax',
    ];

    protected $casts = [
        'bill_id' => 'int',
        'service_id' => 'int',
        'service_sub_total' => 'float',
        'service_sub_total_commission' => 'float',
        'service_tax' => 'float',
    ];

    /** The bill this service belongs to. */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
