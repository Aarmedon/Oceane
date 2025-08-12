<?php

namespace Database\Factories;

use App\Models\Galaxy;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Sector> */
class SectorFactory extends Factory
{
    protected $model = Sector::class;

    public function definition(): array
    {
        $xStart = $this->faker->numberBetween(0, 900);
        $yStart = $this->faker->numberBetween(0, 900);
        $xEnd = $xStart + $this->faker->numberBetween(10, 100);
        $yEnd = $yStart + $this->faker->numberBetween(10, 100);

        return [
            'galaxy_id' => Galaxy::factory(),
            'sector_number' => $this->faker->unique()->numberBetween(1, 9999),
            'name' => 'SEC-'.$this->faker->unique()->bothify('##??'),
            'position_x_start' => $xStart,
            'position_y_start' => $yStart,
            'position_x_end' => $xEnd,
            'position_y_end' => $yEnd,
            'image_path' => null,
        ];
    }
}
