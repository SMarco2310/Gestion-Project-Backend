<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'logo' => null,
            'reminder_days_before_start' => 2,
            'reminder_days_before_end' => 2,
            'reminder_time_start' => '08:00:00',
            'reminder_time_end' => '08:00:00',
        ];
    }
}
