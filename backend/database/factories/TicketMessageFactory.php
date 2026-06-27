<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TicketMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'body' => fake()->paragraph(2),
            'is_internal' => fake()->boolean(30),
        ];
    }
}
