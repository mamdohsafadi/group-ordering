<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Account that can lead or join a group order.
 *
 * @property int $id
 * @property string|null $name
 * @property string $email
 * @property string|null $mobile
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /** Group orders this user created as leader. */
    public function ledGroupOrders(): HasMany
    {
        return $this->hasMany(GroupOrder::class, 'leader_id');
    }

    /** Participation records across group orders. */
    public function groupParticipations(): HasMany
    {
        return $this->hasMany(GroupParticipant::class, 'user_id');
    }

    /** Saved delivery addresses. */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }
}
