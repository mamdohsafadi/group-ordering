<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lookup: bill stage (e.g. PENDING / PAID / CANCELLED).
 *
 * @property int $id
 * @property string $name
 */
class BillStage extends Model
{
    protected $table = 'bill_stage';

    public $timestamps = false;

    protected $fillable = ['name'];
}
