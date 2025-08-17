<?php

namespace Database\Seeders;

use App\Models\Bonus;
use App\Models\Good;
use App\Models\GoodBonus;
use Illuminate\Database\Seeder;

class GoodsBonusSeeder extends Seeder
{
    public function run(): void
    {
        // Map of good_code => [[bonus_code, value], ...]
        $map = [
            'food' => [
                ['population_growth_percent', 10],
            ],
            'agricultural_equipment' => [
                ['food_per_inhabited_planet_flat', 1],
            ],
            'luxury' => [
                ['tax_revenue_percent', 10],
            ],
            'holofilm' => [
                ['stability_percent_per_turn', 1],
            ],
            'alcohol' => [
                ['stability_percent_per_turn', -1],
            ],
            'medicine' => [
                ['population_growth_percent', 5],
            ],
            'software' => [
                ['research_percent', 25],
            ],
            'robots' => [
                ['construction_points_flat', 5],
            ],
            'electronic_components' => [
                ['special_services_budget_percent', 25],
                ['counter_espionage_budget_percent', 25],
            ],
            'armament' => [
                ['stability_percent_per_turn', -1],
                ['militia_mobilization_percent', 25],
            ],
            'fuel' => [
                ['fleet_maintenance_percent', -25],
            ],
            'industrial_parts' => [
                ['building_maintenance_percent', -10],
            ],
            'precious_metals' => [
                ['system_revenue_percent', 5],
            ],
            // 'tixium', 'lixiam', 'oxole' => no bonus/malus
        ];

        $goodCodes = array_keys($map);
        $bonusCodes = [];
        foreach ($map as $pairs) {
            foreach ($pairs as [$b]) {
                $bonusCodes[$b] = true;
            }
        }
        $bonusCodes = array_keys($bonusCodes);

        $goods = Good::whereIn('code', $goodCodes)->get()->keyBy('code');
        $bonuses = Bonus::whereIn('code', $bonusCodes)->get()->keyBy('code');

        foreach ($map as $gCode => $pairs) {
            $good = $goods->get($gCode);
            if (!$good) {
                $this->command?->warn("Good not found: {$gCode}");
                continue;
            }
            foreach ($pairs as [$bCode, $value]) {
                $bonus = $bonuses->get($bCode);
                if (!$bonus) {
                    $this->command?->warn("Bonus not found: {$bCode}");
                    continue;
                }
                GoodBonus::updateOrCreate(
                    [
                        'good_id' => $good->id,
                        'bonus_id' => $bonus->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
