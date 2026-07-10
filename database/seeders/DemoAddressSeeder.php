<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;

/**
 * Two saved addresses per demo user so the start-group-order modal has a
 * real address picker for whoever is logged in. Idempotent.
 */
class DemoAddressSeeder extends Seeder
{
    public function run(): void
    {
        $spots = [
            ['name' => 'Home', 'street' => 'Mazzeh Highway', 'details' => 'Damascus, Mazzeh, Building 12', 'latitude' => 33.5020, 'longitude' => 36.2520],
            ['name' => 'Office', 'street' => 'Baramkeh Street', 'details' => 'Damascus, Baramkeh, Tradinos HQ', 'latitude' => 33.5100, 'longitude' => 36.2890],
        ];

        foreach (User::query()->orderBy('id')->get() as $user) {
            foreach ($spots as $spot) {
                UserAddress::updateOrCreate(
                    ['user_id' => $user->id, 'name' => $spot['name']],
                    $spot,
                );
            }
        }
    }
}
