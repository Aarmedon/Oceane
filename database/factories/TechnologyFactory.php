<?php

namespace Database\Factories;

use App\Models\Technology;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Technology> */
class TechnologyFactory extends Factory
{
    protected $model = Technology::class;

    public function definition(): array
    {
        $categories = [
            Technology::CATEGORY_PROPULSION,
            Technology::CATEGORY_WEAPONS,
            Technology::CATEGORY_SHIELDS,
            Technology::CATEGORY_CONSTRUCTION,
            Technology::CATEGORY_BIOLOGY,
            Technology::CATEGORY_ECONOMY,
            Technology::CATEGORY_SPECIAL,
            Technology::CATEGORY_SENSORS,
        ];
        $types = [
            Technology::TYPE_SIMPLE,
            Technology::TYPE_BUILDING,
            Technology::TYPE_SHIP_COMPONENT,
        ];

        $category = fake()->randomElement($categories);
        $type = fake()->randomElement($types);

        return [
            'name' => ucfirst($category).' Tech '.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(12),
            'category' => $category,
            'base_research_cost' => fake()->randomFloat(2, 100, 10000),
            'level_multiplier' => fake()->randomFloat(2, 1.10, 2.50),
            'max_level' => fake()->numberBetween(3, 10),
            'prerequisite_technology_id' => null,
            'prerequisite_level' => 1,
            'image_path' => null,
            'type' => $type,
        ];
    }
}
