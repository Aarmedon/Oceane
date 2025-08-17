<?php

namespace Database\Factories;

use App\Models\Commander;
use App\Models\Fleet;
use App\Models\StarSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Fleet> */
class FleetFactory extends Factory
{
    protected $model = Fleet::class;

    public function definition(): array
    {
        return [
            'commander_id' => Commander::factory(),
            'name' => 'Flotte '.fake()->unique()->colorName().' '.fake()->randomNumber(3),
            'current_system_id' => StarSystem::factory(),
            'destination_system_id' => null,
            'position_x' => fake()->numberBetween(0, 1000),
            'position_y' => fake()->numberBetween(0, 1000),
            // Derive galaxy from the current system's sector at creation time
            'galaxy_id' => function (array $attributes) {
                $sid = $attributes['current_system_id'] ?? null;
                if ($sid) {
                    $sys = StarSystem::find($sid);
                    if ($sys && $sys->sector) {
                        return $sys->sector->galaxy_id;
                    }
                }
                return null;
            },
            'status' => fake()->randomElement([
                Fleet::STATUS_DOCKED,
                Fleet::STATUS_MOVING,
                Fleet::STATUS_COMBAT,
                Fleet::STATUS_WAITING,
            ]),
            'arrival_turn' => fake()->optional(0.5)->numberBetween(5, 30),
            // leave directive_id null to let model boot set default from config
            'directive_id' => null,
            'hero_id' => null,
            'morale' => fake()->numberBetween(30, 100),
            'experience' => fake()->numberBetween(0, 1000),
            'maintenance_cost' => fake()->numberBetween(50, 5000),
        ];
    }

    // No additional configuration hooks needed
}
