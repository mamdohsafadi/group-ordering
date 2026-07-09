<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo accounts for showcasing the group ordering flow (leader + participants).
 * In production users are owned by the live app; this seeder is demo-only.
 */
class DemoUserSeeder extends Seeder
{
    public const PASSWORD = 'password';

    public function run(): void
    {
        $users = [
            ['name' => 'Hasan Katteeb', 'email' => 'hasan@demo.beeorder.com', 'mobile' => '0930000001'],
            ['name' => 'Lina Haddad', 'email' => 'lina@demo.beeorder.com', 'mobile' => '0930000002'],
            ['name' => 'Omar Nassar', 'email' => 'omar@demo.beeorder.com', 'mobile' => '0930000003'],
            ['name' => 'Maya Aswad', 'email' => 'maya@demo.beeorder.com', 'mobile' => '0930000004'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [...$user, 'password' => Hash::make(self::PASSWORD)],
            );
        }
    }
}
