<?php

namespace Database\Seeders;

use App\Models\Bonus;
use Illuminate\Database\Seeder;

class BonusesSeeder extends Seeder
{
    public function run(): void
    {
        $bonuses = [
            [
                'code' => 'population_growth_percent',
                'name' => 'Croissance de population (%)',
                'domain' => 'population_growth',
                'description' => 'Modifie en % le taux de croissance de la population du système.',
            ],
            [
                'code' => 'research_percent',
                'name' => 'Recherche technologique (%)',
                'domain' => 'research',
                'description' => 'Modifie en % la recherche technologique du système.',
            ],
            [
                'code' => 'construction_points_flat',
                'name' => 'Points de construction (fixe)',
                'domain' => 'construction_points',
                'description' => 'Ajoute des points de construction (valeur fixe) par tour.',
            ],
            [
                'code' => 'food_per_inhabited_planet_flat',
                'name' => 'Alimentation par planète habitée (fixe)',
                'domain' => 'food_production_per_planet',
                'description' => 'Ajoute des unités de produits alimentaires par planète habitée (valeur fixe).',
            ],
            [
                'code' => 'stability_percent_per_turn',
                'name' => 'Stabilité (% par tour)',
                'domain' => 'stability',
                'description' => 'Modifie en % la stabilité du système chaque tour.',
            ],
            [
                'code' => 'militia_mobilization_percent',
                'name' => 'Mobilisation des milices (%)',
                'domain' => 'militia_mobilization',
                'description' => 'Modifie en % la mobilisation des milices de défense planétaire.',
            ],
            [
                'code' => 'fleet_maintenance_percent',
                'name' => "Entretien des flottes (%)",
                'domain' => 'fleet_maintenance',
                'description' => 'Modifie en % les frais d’entretien des flottes présentes dans le système.',
            ],
            [
                'code' => 'building_maintenance_percent',
                'name' => "Entretien des bâtiments (%)",
                'domain' => 'building_maintenance',
                'description' => 'Modifie en % les frais d’entretien des bâtiments planétaires du système.',
            ],
            [
                'code' => 'tax_revenue_percent',
                'name' => 'Impôts (%)',
                'domain' => 'tax_revenue',
                'description' => 'Modifie en % le montant des impôts récoltés.',
            ],
            [
                'code' => 'system_revenue_percent',
                'name' => 'Revenus du système (%)',
                'domain' => 'system_revenue',
                'description' => 'Modifie en % les revenus globaux du système.',
            ],
            [
                'code' => 'special_services_budget_percent',
                'name' => 'Budget services spéciaux (%)',
                'domain' => 'special_services_budget',
                'description' => 'Modifie en % le budget des services spéciaux pour ce système.',
            ],
            [
                'code' => 'counter_espionage_budget_percent',
                'name' => 'Budget contre-espionnage (%)',
                'domain' => 'counter_espionage_budget',
                'description' => 'Modifie en % le budget de contre-espionnage pour ce système.',
            ],
        ];

        foreach ($bonuses as $b) {
            Bonus::updateOrCreate(
                ['code' => $b['code']],
                $b
            );
        }
    }
}
