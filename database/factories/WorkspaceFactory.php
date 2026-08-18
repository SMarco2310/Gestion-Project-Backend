<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    protected $model = Workspace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'organization_id' => Organization::factory(),
            'kanban_columns' => ['À faire', 'En cours', 'Terminé'],
            'kanban_colors' => null,
            'created_by' => User::factory(),
            'color' => null,
        ];
    }
}
