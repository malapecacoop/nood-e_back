<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::factory(10)->create();

        $users = \App\Models\User::all();

        foreach ($users as $user) {
            $user->update(['organization_id' => rand(1, 10)]);
        }
    }
}
