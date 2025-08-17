<?php

namespace Database\Seeders;

use App\Models\Good;
use Illuminate\Database\Seeder;

class GoodsSeeder extends Seeder
{
    public function run(): void
    {
        $goods = [
            [
                'code' => 'food',
                'name' => 'Produits alimentaires',
                'category' => 'consumable',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+10% croissance de population si stock >= 100.",
            ],
            [
                'code' => 'agricultural_equipment',
                'name' => 'Matériel agricole',
                'category' => 'industrial',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+1 unité de produits alimentaires par planète habitée si stock >= 100.",
            ],
            [
                'code' => 'luxury',
                'name' => 'Articles de luxe',
                'category' => 'consumable',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+10% impôts si stock >= 100.",
            ],
            [
                'code' => 'holofilm',
                'name' => 'Holofilms et hololivres',
                'category' => 'culture',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+1% stabilité par tour si stock >= 100.",
            ],
            [
                'code' => 'alcohol',
                'name' => 'Alcools et drogues',
                'category' => 'consumable',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "-1% stabilité par tour si stock >= 100.",
            ],
            [
                'code' => 'medicine',
                'name' => 'Médicaments',
                'category' => 'medical',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+5% croissance de population si stock >= 100.",
            ],
            [
                'code' => 'software',
                'name' => 'Logiciels',
                'category' => 'technology',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+25% recherche technologique si stock >= 100.",
            ],
            [
                'code' => 'robots',
                'name' => 'Robots',
                'category' => 'industrial',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+5 points de construction si stock >= 100.",
            ],
            [
                'code' => 'electronic_components',
                'name' => 'Composants électroniques',
                'category' => 'technology',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+25% budgets services spéciaux et contre-espionnage si stock >= 100.",
            ],
            [
                'code' => 'armament',
                'name' => 'Armement et explosifs',
                'category' => 'military',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "-1% stabilité par tour et +25% mobilisation milices si stock >= 100.",
            ],
            [
                'code' => 'fuel',
                'name' => 'Carburants',
                'category' => 'industrial',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "-25% frais d'entretien des flottes dans le système si stock >= 100.",
            ],
            [
                'code' => 'industrial_parts',
                'name' => 'Pièces industrielles',
                'category' => 'industrial',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "-10% entretien des bâtiments planétaires si stock >= 100.",
            ],
            [
                'code' => 'precious_metals',
                'name' => 'Métaux précieux',
                'category' => 'resource',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "+5% revenus du système si stock >= 100.",
            ],
            [
                'code' => 'tixium',
                'name' => 'Tixium',
                'category' => 'rare_mineral',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "Pas de bonus/malus. Produit uniquement naturellement. Requis pour certaines constructions.",
            ],
            [
                'code' => 'lixiam',
                'name' => 'Lixiam',
                'category' => 'rare_mineral',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "Pas de bonus/malus. Produit uniquement naturellement. Requis pour certaines constructions.",
            ],
            [
                'code' => 'oxole',
                'name' => 'Oxole',
                'category' => 'rare_mineral',
                'unit' => 'unit',
                'base_price' => 5,
                'is_tradeable' => true,
                'description' => "Pas de bonus/malus. Produit uniquement naturellement. Requis pour certaines constructions.",
            ],
        ];

        foreach ($goods as $g) {
            Good::updateOrCreate(
                ['code' => $g['code']],
                $g
            );
        }
    }
}
