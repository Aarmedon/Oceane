<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Race;
use Illuminate\Support\Str;

class RaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Original races from o2 (order is critical and used across constants)
        // Names source: `zIgzAg/jeu/oceane/Messages.java` RACES
        // Colors and selection weights source: `site/registre/race.txt`
        // Habitat, atmosphere modifiers and characteristics source: `zIgzAg/jeu/oceane/Const.java`
        $races = [
            [
                'name' => 'Humain',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 0,
                        'tech_bonus_pct' => 0,
                        'space_combat_pct' => 0,
                        'planet_combat_pct' => 0,
                    ],
                    'habitat' => [
                        'radiation' => [1, 85],
                        'temperature' => [-80, 70],
                        'gravity' => [1, 40],
                    ],
                    // atmospheres order: ideale, vivifiante, classique, toxique, tres_toxique
                    'atmosphere_modifiers' => [2, 1, 0, -1, -2],
                    'ui' => ['color' => '#d3d3d3'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => 'battlaII',
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Zorglub',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 5,
                        'tech_bonus_pct' => 5,
                        'space_combat_pct' => -10,
                        'planet_combat_pct' => -5,
                    ],
                    'habitat' => [
                        'radiation' => [0, 150],
                        'temperature' => [0, 200],
                        'gravity' => [1, 40],
                    ],
                    'atmosphere_modifiers' => [0, 0, 1, 2, 3],
                    'ui' => ['color' => '#8A2BE2'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => 'moteurIII',
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Golo',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => -2,
                        'tech_bonus_pct' => -10,
                        'space_combat_pct' => 5,
                        'planet_combat_pct' => 15,
                    ],
                    'habitat' => [
                        'radiation' => [5, 75],
                        'temperature' => [-150, 10],
                        'gravity' => [1, 100],
                    ],
                    'atmosphere_modifiers' => [-1, 0, 3, 0, -1],
                    'ui' => ['color' => '#7fffd4'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => 'radarV',
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Yozda',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 10,
                        'tech_bonus_pct' => 0,
                        'space_combat_pct' => -5,
                        'planet_combat_pct' => -15,
                    ],
                    'habitat' => [
                        'radiation' => [0, 200],
                        'temperature' => [-150, 200],
                        'gravity' => [0, 100],
                    ],
                    'atmosphere_modifiers' => [-3, -2, -1, 0, 2],
                    'ui' => ['color' => '#FF1493'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => 'missIII',
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Jondoïshi',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 0,
                        'tech_bonus_pct' => 15,
                        'space_combat_pct' => -5,
                        'planet_combat_pct' => -5,
                    ],
                    'habitat' => [
                        'radiation' => [10, 70],
                        'temperature' => [-150, 200],
                        'gravity' => [10, 35],
                    ],
                    'atmosphere_modifiers' => [2, 1, 0, 1, 2],
                    'ui' => ['color' => '#7CFC00'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => null,
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Nomade',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 0,
                        'tech_bonus_pct' => 0,
                        'space_combat_pct' => 0,
                        'planet_combat_pct' => 0,
                    ],
                    'habitat' => [
                        'radiation' => [1, 110],
                        'temperature' => [-80, 70],
                        'gravity' => [1, 35],
                    ],
                    'atmosphere_modifiers' => [2, 1, 0, -1, -2],
                    'ui' => ['color' => '#ffdead'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => null,
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Drewin',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => -2,
                        'tech_bonus_pct' => -5,
                        'space_combat_pct' => 0,
                        'planet_combat_pct' => 10,
                    ],
                    'habitat' => [
                        'radiation' => [50, 200],
                        'temperature' => [-50, 100],
                        'gravity' => [1, 40],
                    ],
                    'atmosphere_modifiers' => [3, 2, 0, -2, -3],
                    'ui' => ['color' => '#90ee90'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => null,
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Tonk',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 0,
                        'tech_bonus_pct' => -10,
                        'space_combat_pct' => 15,
                        'planet_combat_pct' => 0,
                    ],
                    'habitat' => [
                        'radiation' => [0, 200],
                        'temperature' => [-150, 200],
                        'gravity' => [30, 100],
                    ],
                    'atmosphere_modifiers' => [0, 0, 0, 0, 0],
                    'ui' => ['color' => '#b8860b'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => null,
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Golub',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 10,
                        'tech_bonus_pct' => 0,
                        'space_combat_pct' => -10,
                        'planet_combat_pct' => 10,
                    ],
                    'habitat' => [
                        'radiation' => [50, 150],
                        'temperature' => [-60, 80],
                        'gravity' => [1, 60],
                    ],
                    'atmosphere_modifiers' => [0, 0, 2, 1, 1],
                    'ui' => ['color' => '#87cefa'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => null,
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
            [
                'name' => 'Zooush',
                'description' => null,
                'bonuses' => [
                    'modifiers' => [
                        'population_growth_pct' => 0,
                        'tech_bonus_pct' => 15,
                        'space_combat_pct' => 0,
                        'planet_combat_pct' => -10,
                    ],
                    'habitat' => [
                        'radiation' => [0, 100],
                        'temperature' => [-100, 120],
                        'gravity' => [0, 25],
                    ],
                    'atmosphere_modifiers' => [1, 1, -3, 1, 1],
                    'ui' => ['color' => '#ffa07a'],
                    'selection_weight' => 1000,
                    'starting_tech_code' => null,
                ],
                'penalties' => null,
                'is_playable' => true,
            ],
        ];
        
        foreach ($races as $raceData) {
            Race::updateOrCreate(
                ['name' => $raceData['name']],
                [
                    'slug' => Str::slug($raceData['name']),
                    'description' => $raceData['description'],
                    'bonuses' => $raceData['bonuses'],
                    'penalties' => $raceData['penalties'],
                    'image_path' => 'images/races/' . Str::slug($raceData['name']) . '.png',
                    'is_playable' => $raceData['is_playable'],
                ]
            );
        }
    }
}
