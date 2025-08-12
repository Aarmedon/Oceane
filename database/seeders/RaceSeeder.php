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
        $races = [
            [
                'name' => 'Humains',
                'description' => 'Race polyvalente avec des bonus en diplomatie et commerce.',
                'bonuses' => json_encode(['diplomatie' => 10, 'commerce' => 15]),
                'penalties' => json_encode(['recherche' => -5]),
                'is_playable' => true,
            ],
            [
                'name' => 'Zorg',
                'description' => 'Race guerrière avec des bonus en combat et construction de vaisseaux.',
                'bonuses' => json_encode(['combat' => 20, 'construction' => 10]),
                'penalties' => json_encode(['diplomatie' => -15]),
                'is_playable' => true,
            ],
            [
                'name' => 'Eldari',
                'description' => 'Race ancienne avec des bonus en recherche et technologie.',
                'bonuses' => json_encode(['recherche' => 25, 'technologie' => 15]),
                'penalties' => json_encode(['production' => -10]),
                'is_playable' => true,
            ],
            [
                'name' => 'Nexus',
                'description' => 'Race cybernétique avec des bonus en production et efficacité.',
                'bonuses' => json_encode(['production' => 20, 'efficacité' => 15]),
                'penalties' => json_encode(['croissance' => -15]),
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
