<?php

namespace Database\Factories;

use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Race> */
class RaceFactory extends Factory
{
    protected $model = Race::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Humains','Zyriens','Khel','Vorlans','Tauris','Nerathi']);
        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1,9999)),
            'description' => fake()->sentence(12),
            'is_playable' => true,
            'image_path' => null,
        ];
    }
}
