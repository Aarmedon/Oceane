<?php

namespace App\Services;

use App\Models\Good;
use App\Models\StarSystem;
use App\Models\SystemGood;
use App\Models\Commander;
use Illuminate\Support\Facades\DB;

class EconomyManager
{
    /**
     * Agrège la production naturelle des planètes vers system_goods.production_per_turn
     * Regroupement par (star_system_id, commander_id, good_id)
     */
    public function aggregateSystemProduction(int $currentTurn): void
    {
        // Mettre à 0 toutes les productions par défaut, puis recalculer
        DB::table('system_goods')->update(['production_per_turn' => 0]);

        $rows = DB::table('natural_goods as ng')
            ->join('planets as p', 'p.id', '=', 'ng.planet_id')
            ->select('p.star_system_id', 'p.commander_id', 'ng.good_id', DB::raw('SUM(ng.production_per_turn) as prod'))
            ->whereNotNull('p.commander_id')
            ->groupBy('p.star_system_id', 'p.commander_id', 'ng.good_id')
            ->get();

        foreach ($rows as $r) {
            if (!$r->commander_id) {
                continue; // sécurité
            }
            SystemGood::updateOrCreate(
                [
                    'star_system_id' => $r->star_system_id,
                    'commander_id' => $r->commander_id,
                    'good_id' => $r->good_id,
                ],
                [
                    'production_per_turn' => (int) $r->prod,
                    'last_updated_turn' => $currentTurn,
                ]
            );
        }
    }

    /**
     * Résolution du marché pour le tour:
     * - Ajoute la production au stock
     * - Ajuste le prix selon la règle legacy (relaxation vers le prix moyen de l'univers)
     *   Formule inspirée de Possession#setPrixMarchandise / relaxation:
     *   newPrice = price - ((100 - taux)/100) * ((price - avg) / (2 + stock/10))
     *   - Quand on ajoute des marchandises: price -= quantite (avant relaxation)
     *   - Clamp prix à [1, 100] (prix en dixièmes)
     */
    public function resolveMarketTurn(int $currentTurn): void
    {
        $systemGoods = SystemGood::with(['good', 'starSystem'])->get();

        // Calculer la moyenne de l'univers par marchandise à partir des prix existants
        $avgMap = $this->getUniverseAveragePrices();

        foreach ($systemGoods as $sg) {
            $good = $sg->good;
            $system = $sg->starSystem;
            if (!$good || !$system) {
                continue;
            }

            // Prix moyen de l'univers: d'abord moyenne observée, sinon base_price (unités) converti en dixièmes
            $fallbackAvg = max(1, min(100, (int) round(($good->base_price ?? 5) * 10)));
            $avg = (int) ($avgMap[$good->id] ?? $fallbackAvg);

            // Prix actuel en dixièmes; init si null
            $price = (int) ($sg->price ?? $avg);
            $price = max(1, min(100, $price));

            // Ajout de la production au stock et effet sur le prix (price -= production)
            $added = (int) ($sg->production_per_turn ?? 0);
            if ($added !== 0) {
                $sg->stock = (int) $sg->stock + $added;
                $price = $price - $added;
                $price = max(1, min(100, $price));
            }

            // Relaxation vers le prix moyen (legacy): dépend du taux (politique/impôts)
            $rate = $this->getRelaxationRateForSystem($system); // 0..100
            $stockForRelax = (int) $sg->stock; // après ajout
            $den = 2.0 + ($stockForRelax / 10.0);
            $diff = $price - $avg;
            $factor = (100 - $rate) / 100.0; // si taux élevé, relaxation plus lente
            $delta = $factor * ($diff / $den);

            $newPrice = (int) round($price - $delta);
            $newPrice = max(1, min(100, $newPrice));

            $sg->price = $newPrice;
            $sg->last_updated_turn = $currentTurn;
            $sg->save();
        }
    }

    /**
     * Retourne la moyenne des prix (1..100) par marchandise sur l'ensemble de l'univers.
     * Ne considère que les enregistrements ayant un prix non nul.
     * @return array<int,int> good_id => avg_price
     */
    private function getUniverseAveragePrices(): array
    {
        $rows = DB::table('system_goods')
            ->select('good_id', DB::raw('AVG(price) as avg_price'))
            ->whereNotNull('price')
            ->groupBy('good_id')
            ->get();

        $avg = [];
        foreach ($rows as $r) {
            $avg[(int) $r->good_id] = (int) round(max(1, min(100, (float) $r->avg_price)));
        }
        return $avg;
    }

    /**
     * Agrège les bonus actifs d'un système contrôlé: somme des valeurs de bonus pour toutes les marchandises
     * dont le stock >= 100 pour le couple (system, commander).
     * Retourne un tableau [code_bonus => valeur_totale].
     */
    public function getActiveBonusesForSystem(StarSystem $system): array
    {
        if (!$system->commander_id) {
            return [];
        }

        $rows = DB::table('system_goods as sg')
            ->join('goods_bonus as gb', 'gb.good_id', '=', 'sg.good_id')
            ->join('bonuses as b', 'b.id', '=', 'gb.bonus_id')
            ->select('b.code', DB::raw('SUM(gb.value) as total_value'))
            ->where('sg.star_system_id', $system->id)
            ->where('sg.commander_id', $system->commander_id)
            ->where('sg.stock', '>=', 100)
            ->groupBy('b.code')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->code] = (int) $r->total_value;
        }
        return $out;
    }

    /**
     * Agrège les bonus actifs pour l'ensemble des systèmes contrôlés par un commandant.
     * Additionne les valeurs de bonus pour toutes les marchandises dont le stock >= 100
     * pour le couple (star_system_id, commander_id) du commandant indiqué.
     * Retourne un tableau [code_bonus => valeur_totale].
     */
    public function getActiveBonusesForCommander(Commander $commander): array
    {
        $commanderId = (int) $commander->id;
        if ($commanderId <= 0) {
            return [];
        }

        $rows = DB::table('system_goods as sg')
            ->join('goods_bonus as gb', 'gb.good_id', '=', 'sg.good_id')
            ->join('bonuses as b', 'b.id', '=', 'gb.bonus_id')
            ->select('b.code', DB::raw('SUM(gb.value) as total_value'))
            ->where('sg.commander_id', $commanderId)
            ->where('sg.stock', '>=', 100)
            ->groupBy('b.code')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->code] = (int) $r->total_value;
        }
        return $out;
    }

    /**
     * Détermine le taux utilisé dans la relaxation des prix.
     * Alignement provisoire sur tax_rate (0..100) du système.
     * TODO: si une Politique économique dédiée existe, la brancher ici.
     */
    private function getRelaxationRateForSystem(StarSystem $system): int
    {
        $rate = (int) ($system->tax_rate ?? 0);
        if ($rate < 0) $rate = 0;
        if ($rate > 100) $rate = 100;
        return $rate;
    }
}
