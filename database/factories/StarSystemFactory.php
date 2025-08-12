<?php

namespace Database\Factories;

use App\Models\Sector;
use App\Models\StarSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\StarSystem> */
class StarSystemFactory extends Factory
{
    protected $model = StarSystem::class;

    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'name' => 'SYS-'.strtoupper(fake()->bothify('??###')),
            'position_x' => fake()->numberBetween(0, 1000),
            'position_y' => fake()->numberBetween(0, 1000),
            'star_type' => fake()->numberBetween(1, 5),
            // is_controlled default false
            // commander_id nullable
            // budgets/policy default values from migration
        ];
    }
}
