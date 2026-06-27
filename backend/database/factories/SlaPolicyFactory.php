<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SlaPolicyFactory extends Factory
{
    public function definition(): array
    {
        $responseTimes = ['low' => 480, 'medium' => 240, 'high' => 120, 'urgent' => 60];
        $resolutionTimes = ['low' => 2880, 'medium' => 1440, 'high' => 720, 'urgent' => 360];
        $priority = fake()->randomElement(['low', 'medium', 'high', 'urgent']);

        return [
            'name' => fake()->words(3, true) . ' SLA',
            'priority' => $priority,
            'response_time_minutes' => $responseTimes[$priority],
            'resolution_time_minutes' => $resolutionTimes[$priority],
            'is_active' => true,
        ];
    }
}
