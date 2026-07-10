<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-bill tax breakdown.
 *
 * @property int $id
 * @property int $bill_id
 * @property float $consumption_tax
 * @property float $local_fees_tax
 * @property float $re_building_tax
 */
class BillTax extends Model
{
    protected $table = 'bill_taxs';

    public $timestamps = false;

    protected $fillable = [
        'bill_id',
        'consumption_tax',
        'local_fees_tax',
        're_building_tax',
    ];

    protected $casts = [
        'bill_id' => 'int',
        'consumption_tax' => 'float',
        'local_fees_tax' => 'float',
        're_building_tax' => 'float',
    ];

    /** The bill this tax breakdown belongs to. */
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'bill_id');
    }
}
