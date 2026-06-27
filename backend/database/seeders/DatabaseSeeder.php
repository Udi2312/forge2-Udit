<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketMessage;
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

                // Make sure at least 2 users are agents/admins
                $users->first()->update(['role' => 'admin']);
                $users->get(1)->update(['role' => 'agent']);

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
                        // Attach random tags
                        $ticket->tags()->attach(
                            $tags->random(rand(0, 2))->pluck('id')->toArray()
                        );

                        // Legacy comments
                        Comment::factory()->count(rand(1, 3))->create([
                            'ticket_id' => $ticket->id,
                            'author_id' => $users->random()->id,
                        ]);

                        // Conversation messages
                        TicketMessage::factory()->count(rand(2, 5))->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->random()->id,
                        ]);

                        // Activity log: creation event
                        ActivityLog::factory()->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->first()->id,
                            'event' => 'created',
                            'field' => null,
                            'old_value' => null,
                            'new_value' => null,
                        ]);

                        // Random activity events
                        ActivityLog::factory()->count(rand(1, 3))->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->random()->id,
                        ]);
                    });
            });
    }
}
