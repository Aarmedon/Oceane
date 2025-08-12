<?php

namespace Database\Factories;

use App\Models\Galaxy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Galaxy> */
class GalaxyFactory extends Factory
{
    protected $model = Galaxy::class;

    public function definition(): array
    {
        return [
            'name' => 'GAL-'.strtoupper(fake()->bothify('??#')),
            'size_x' => fake()->numberBetween(100, 1000),
            'size_y' => fake()->numberBetween(100, 1000),
            'image_path' => null,
        ];
    }
}
