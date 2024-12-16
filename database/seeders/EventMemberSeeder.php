<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EventMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = \App\Models\Event::all();

        $users = \App\Models\User::all();

        foreach ($events as $event) {
            $members = $users->where('id', '!=', $event->author_id)->random(3)->pluck('id');
            $event->members()->attach($members);
        }
    }
}
