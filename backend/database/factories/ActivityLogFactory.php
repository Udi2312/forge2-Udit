<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        $events = ['created', 'status_changed', 'priority_changed', 'assigned', 'message_sent'];

        return [
            'event' => fake()->randomElement($events),
            'field' => fake()->optional()->randomElement(['status', 'priority', 'assignee_id']),
            'old_value' => fake()->optional()->word(),
            'new_value' => fake()->optional()->word(),
        ];
    }
}
