<?php

namespace Database\Factories;

use App\Models\Commander;
use App\Models\User;
use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Commander> */
class CommanderFactory extends Factory
{
    protected $model = Commander::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->name().' '.fake()->randomElement(['I','II','III','IV']),
            // Ensure a valid race by default to satisfy NOT NULL constraint in tests
            'race_id' => Race::factory(),
            'credits' => fake()->numberBetween(1000, 100000),
            'reputation' => fake()->numberBetween(-50, 100),
            'capital_system_id' => null,
            'description' => fake()->sentence(8),
            'avatar_path' => null,
            'created_turn' => fake()->numberBetween(1, 10),
        ];
    }
}
