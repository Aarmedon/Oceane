<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MapSprite;

class MapSpritesSeeder extends Seeder
{
    public function run(): void
    {
        // Insert default rule placeholders (inactive by default)
        $rows = [];

        $systemCategories = ['self', 'ally', 'neutral', 'unknown']; // 'enemy' reserved for future
        $priority = 10;
        foreach ($systemCategories as $cat) {
            $rows[] = [
                'scope' => 'system',
                'owner_category' => $cat,
                'size_min' => null,
                'size_max' => null,
                'priority' => $priority,
                'image_path' => "sprites/systems/{$cat}.png",
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $priority += 10;
        }

        $fleetCategories = ['self', 'ally', 'neutral', 'unknown'];
        $bins = [
            ['min' => 1, 'max' => 10, 'slug' => '1_10'],
            ['min' => 11, 'max' => 50, 'slug' => '11_50'],
            ['min' => 51, 'max' => null, 'slug' => '51_plus'],
        ];

        foreach ($fleetCategories as $baseIdx => $cat) {
            foreach ($bins as $idx => $b) {
                $rows[] = [
                    'scope' => 'fleet',
                    'owner_category' => $cat,
                    'size_min' => $b['min'],
                    'size_max' => $b['max'],
                    'priority' => 10 + $baseIdx * 10 + $idx, // stable but not important due to non-overlapping bins
                    'image_path' => "sprites/fleets/{$cat}_{$b['slug']}.png",
                    'is_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Optional catch-all 'any' rules (lowest priority)
        $rows[] = [
            'scope' => 'system',
            'owner_category' => 'any',
            'size_min' => null,
            'size_max' => null,
            'priority' => 999,
            'image_path' => 'sprites/systems/any.png',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $rows[] = [
            'scope' => 'fleet',
            'owner_category' => 'any',
            'size_min' => null,
            'size_max' => null,
            'priority' => 999,
            'image_path' => 'sprites/fleets/any.png',
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        MapSprite::insert($rows);
    }
}
