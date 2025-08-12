<?php

namespace Database\Factories;

use App\Models\Commander;
use App\Models\ShipDesign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\ShipDesign> */
class ShipDesignFactory extends Factory
{
    protected $model = ShipDesign::class;

    public function definition(): array
    {
        $maxHull = fake()->numberBetween(80, 400);
        $maxShield = fake()->numberBetween(0, 300);
        return [
            'name' => fake()->colorName().'-class',
            'description' => fake()->sentence(8),
            'creator_id' => Commander::factory(),
            // size is integer in migration
            'size' => fake()->numberBetween(1, 3),
            'base_cost' => fake()->randomFloat(2, 1000, 50000),
            'construction_time' => fake()->numberBetween(1, 12),
            'max_hull_points' => $maxHull,
            'max_shield_points' => $maxShield,
            'power_generation' => fake()->numberBetween(10, 200),
            'cargo_capacity' => fake()->numberBetween(0, 500),
            'maintenance_cost' => fake()->randomFloat(2, 10, 200),
            'royalties_rate' => fake()->randomFloat(2, 0, 5),
            'is_public' => fake()->boolean(30),
            'combat_power' => fake()->numberBetween(50, 800),
            'image_path' => null,
        ];
    }
}
