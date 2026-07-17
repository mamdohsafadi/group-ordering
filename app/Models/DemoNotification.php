<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A demo in-app notification (stand-in for the live push system).
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property array|null $payload
 * @property Carbon|null $read_at
 */
class DemoNotification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'payload',
        'read_at',
    ];

    protected $casts = [
        'user_id' => 'int',
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    /** The recipient. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
