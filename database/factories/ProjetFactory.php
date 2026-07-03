<?php

namespace Database\Factories;

use App\Models\Projet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Projet>
 */
class ProjetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['à faire', 'en cours', 'terminé']),
            'start_date' => fake()->date(),
            'end_date' => fake()->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'user_id' => \App\Models\User::factory(),
            'organization_id' => \App\Models\Organization::factory(),
        ];
    }
}
