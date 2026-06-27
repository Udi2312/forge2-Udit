<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\SlaPolicy;
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

                $users->first()->update(['role' => 'admin']);
                $users->get(1)->update(['role' => 'agent']);

                // Create SLA policies per priority
                foreach (['low', 'medium', 'high', 'urgent'] as $priority) {
                    SlaPolicy::factory()->create([
                        'organization_id' => $org->id,
                        'priority' => $priority,
                        'name' => ucfirst($priority) . ' Priority SLA',
                    ]);
                }

                // Create tags
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
                        $ticket->tags()->attach(
                            $tags->random(rand(0, 2))->pluck('id')->toArray()
                        );

                        Comment::factory()->count(rand(1, 3))->create([
                            'ticket_id' => $ticket->id,
                            'author_id' => $users->random()->id,
                        ]);

                        TicketMessage::factory()->count(rand(2, 5))->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->random()->id,
                        ]);

                        ActivityLog::factory()->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->first()->id,
                            'event' => 'created',
                            'field' => null,
                            'old_value' => null,
                            'new_value' => null,
                        ]);

                        ActivityLog::factory()->count(rand(1, 3))->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->random()->id,
                        ]);

                        // Random notification for assignee/requester
                        Notification::factory()->count(rand(0, 2))->create([
                            'ticket_id' => $ticket->id,
                            'user_id' => $users->random()->id,
                        ]);
                    });
            });
    }
}
