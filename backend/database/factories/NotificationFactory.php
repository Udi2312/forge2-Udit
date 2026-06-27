<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $types = ['status_changed', 'assigned', 'ticket_created', 'message_sent', 'sla_breach'];

        return [
            'type' => fake()->randomElement($types),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(1),
            'is_read' => fake()->boolean(30),
        ];
    }
}
