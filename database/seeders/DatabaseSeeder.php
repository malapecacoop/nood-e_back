<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(RoomSeeder::class);
        $this->call(EventSeeder::class);
        $this->call(EventMemberSeeder::class);
        $this->call(DiscussionSeeder::class);
        $this->call(DiscussionMemberSeeder::class);
        $this->call(TopicSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(OrganizationSeeder::class);
        $this->call(AdminUserSeeder::class);
    }
}
