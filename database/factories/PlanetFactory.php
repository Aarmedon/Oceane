<?php

namespace Database\Factories;

use App\Models\Planet;
use App\Models\StarSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Planet> */
class PlanetFactory extends Factory
{
    protected $model = Planet::class;

    public function definition(): array
    {
        return [
            'star_system_id' => StarSystem::factory(),
            'name' => 'PLA-'.strtoupper($this->faker->bothify('??###')),
            'position_in_system' => $this->faker->unique()->numberBetween(1, 12),
            'size' => $this->faker->numberBetween(1, 10),
            'type' => $this->faker->numberBetween(1, 6),
            'mineral_resources' => $this->faker->numberBetween(0, 100),
            'radiation_level' => $this->faker->numberBetween(0, 100),
            'temperature' => $this->faker->numberBetween(-200, 200),
            'gravity' => $this->faker->numberBetween(1, 30),
            'atmosphere_type' => $this->faker->numberBetween(0, 5),
            'image_path' => null,
            'is_colonized' => false,
            'commander_id' => null,
        ];
    }
}
