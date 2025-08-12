<?php

namespace Database\Factories;

use App\Models\Fleet;
use App\Models\Ship;
use App\Models\ShipDesign;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Ship> */
class ShipFactory extends Factory
{
    protected $model = Ship::class;

    public function definition(): array
    {
        $maxHull = fake()->numberBetween(50, 300);
        $hull = fake()->numberBetween((int)($maxHull/2), $maxHull);
        $maxShield = fake()->numberBetween(0, 200);
        $shield = $maxShield ? fake()->numberBetween((int)($maxShield/3), $maxShield) : 0;

        return [
            'fleet_id' => Fleet::factory(),
            'ship_design_id' => ShipDesign::factory(),
            'name' => fake()->randomElement(['Aegis','Nova','Valiant','Aurora','Orion','Zephyr']).' '.fake()->randomNumber(4),
            'hull_points' => $hull,
            'max_hull_points' => $maxHull,
            'shield_points' => $shield,
            'max_shield_points' => $maxShield,
            'experience' => fake()->numberBetween(0, 200),
            'damage_level' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement([
                \App\Models\Ship::STATUS_OPERATIONAL,
                \App\Models\Ship::STATUS_DAMAGED,
                \App\Models\Ship::STATUS_CRITICAL,
            ]),
        ];
    }
}
