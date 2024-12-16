<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'invite_accepted_at' => now(),
            'role_id' => 2,
        ]);

        User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'invite_accepted_at' => now(),
            'role_id' => 3,
        ]);
    }
}
