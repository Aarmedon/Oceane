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
            'galaxy_id' => null, // will be backfilled in afterCreating if not set
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

    /**
     * Ensure galaxy_id coherence with current_system's sector after creation
     */
    public function configure()
    {
        return $this->afterCreating(function (Fleet $fleet) {
            if (empty($fleet->galaxy_id) && $fleet->currentSystem && $fleet->currentSystem->sector) {
                $fleet->galaxy_id = $fleet->currentSystem->sector->galaxy_id;
                $fleet->save();
            }
        });
    }
}
