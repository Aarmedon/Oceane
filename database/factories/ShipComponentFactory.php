<?php

namespace Database\Factories;

use App\Models\ShipComponent;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\ShipComponent> */
class ShipComponentFactory extends Factory
{
    protected $model = ShipComponent::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['weapon','engine','shield','utility']);
        $sub = match ($type) {
            'weapon' => fake()->randomElement(['laser','missile','railgun']),
            'engine' => fake()->randomElement(['impulse','warp','hyperdrive']),
            'shield' => fake()->randomElement(['deflector','plasma','kinetic']),
            default => fake()->randomElement(['scanner','ecm','cargo'])
        };

        return [
            'name' => ucfirst($sub).' Mk'.fake()->numberBetween(1,5),
            'description' => fake()->sentence(8),
            'type' => $type,
            'subtype' => $sub,
            'cost' => fake()->randomFloat(2, 100, 10000),
            'size' => fake()->numberBetween(1, 10),
            'power_requirement' => fake()->numberBetween(1, 50),
            'technology_id' => Technology::factory(),
            'required_technology_level' => fake()->numberBetween(0, 3),
            'special_characteristics' => [],
            'weapon_speed' => $type==='weapon' ? fake()->numberBetween(1000, 5000) : null,
            'shield_damage' => $type==='weapon' ? fake()->numberBetween(5, 50) : null,
            'hull_damage' => $type==='weapon' ? fake()->numberBetween(5, 50) : null,
            'ground_damage' => $type==='weapon' ? fake()->numberBetween(0, 20) : null,
            'weapon_range' => $type==='weapon' ? fake()->numberBetween(10, 200) : null,
            'reliability' => fake()->numberBetween(70, 100),
            'image_path' => null,
        ];
    }
}
