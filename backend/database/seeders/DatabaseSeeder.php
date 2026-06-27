<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create 3 organizations with users and tickets
        Organization::factory()
            ->has(User::factory()->count(5), 'users')
            ->count(3)
            ->create()
            ->each(function ($org) {
                $users = $org->users;

                Ticket::factory()
                    ->count(10)
                    ->create([
                        'organization_id' => $org->id,
                        'requester_id' => $users->random()->id,
                    ])
                    ->each(function ($ticket) use ($users) {
                        Comment::factory()
                            ->count(rand(1, 4))
                            ->create([
                                'ticket_id' => $ticket->id,
                                'author_id' => $users->random()->id,
                            ]);
                    });
            });
    }
}
