<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Organization::factory()
            ->has(User::factory()->count(5), 'users')
            ->count(3)
            ->create()
            ->each(function ($org) {
                $users = $org->users;

                // Create tags per org
                $tags = Tag::factory()->count(4)->create([
                    'organization_id' => $org->id,
                ]);

                Ticket::factory()
                    ->count(10)
                    ->create([
                        'organization_id' => $org->id,
                        'requester_id' => $users->random()->id,
                    ])
                    ->each(function ($ticket) use ($users, $tags) {
                        // Assign random tags
                        $ticket->tags()->attach(
                            $tags->random(rand(0, 2))->pluck('id')->toArray()
                        );

                        // Add comments
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
