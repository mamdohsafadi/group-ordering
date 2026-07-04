<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lookup: bill type (e.g. PICKUP / DELIVERY).
 *
 * @property int         $id
 * @property string|null $name
 * @property string|null $arabic_name
 */
class BillType extends Model
{
    protected $table = 'bill_type';

    public $timestamps = false;

    protected $fillable = ['name', 'arabic_name'];
}
