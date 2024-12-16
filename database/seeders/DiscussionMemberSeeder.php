<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DiscussionMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discussions = \App\Models\Discussion::all();

        $users = \App\Models\User::all();

        foreach ($discussions as $discussion) {
            $members = $users->where('id', '!=', $discussion->author_id)->random(3)->pluck('id');
            $discussion->members()->attach($members);
        }
    }
}
