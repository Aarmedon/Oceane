<?php

namespace Database\Factories;

use App\Models\Directive;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Directive> */
class DirectiveFactory extends Factory
{
    protected $model = Directive::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Patrol','Defend','Explore','Escort','Raid','Colonize']),
            'description' => fake()->sentence(8),
        ];
    }
}
