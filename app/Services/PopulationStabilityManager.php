<?php

namespace App\Services;

use App\Models\StarSystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PopulationStabilityManager
{
    private EconomyManager $economyManager;

    public function __construct(EconomyManager $economyManager)
    {
        $this->economyManager = $economyManager;
    }

    /**
     * Apply population growth and stability adjustments per system for the turn,
     * driven by goods bonuses with stock >= 100.
     *
     * Runtime schema checks are performed so this can run even if populations table
     * or star_systems.stability do not exist yet.
     */
    public function applyPopulationAndStabilityForTurn(int $currentTurn): void
    {
        $hasPopTable = Schema::hasTable('populations');
        $popColumn = null;
        $hasPopPlanetId = false;
        if ($hasPopTable) {
            try {
                $cols = Schema::getColumnListing('populations');
                $hasPopPlanetId = in_array('planet_id', $cols, true);
                foreach (['population', 'size', 'amount', 'count'] as $c) {
                    if (in_array($c, $cols, true)) {
                        $popColumn = $c;
                        break;
                    }
                }
                if ($popColumn === null || !$hasPopPlanetId) {
                    $hasPopTable = false; // no usable numeric column
                }
            } catch (\Throwable $e) {
                $hasPopTable = false;
            }
        }

        $hasStability = Schema::hasColumn('star_systems', 'stability');
        $hasColonizedFlag = Schema::hasColumn('planets', 'is_colonized');

        $systems = StarSystem::with(['planets' => function ($q) use ($hasColonizedFlag) {
            if ($hasColonizedFlag) {
                $q->where('is_colonized', true);
            }
        }])->whereNotNull('commander_id')->get();

        foreach ($systems as $system) {
            $bonuses = $this->economyManager->getActiveBonusesForSystem($system);
            $popPct = (int) ($bonuses['population_growth_percent'] ?? 0);
            $stabPct = (int) ($bonuses['stability_percent_per_turn'] ?? 0);

            $updatedPlanets = 0;
            $popDeltaSum = 0;

            if ($hasPopTable && $popColumn && $hasPopPlanetId && $popPct !== 0 && $system->planets->isNotEmpty()) {
                $planetIds = $system->planets->pluck('id')->all();
                $rows = DB::table('populations')
                    ->whereIn('planet_id', $planetIds)
                    ->get(['id', 'planet_id', $popColumn]);

                foreach ($rows as $row) {
                    $current = (int) ($row->{$popColumn} ?? 0);
                    if ($current <= 0) {
                        continue;
                    }
                    $new = (int) floor($current * (1 + $popPct / 100.0));
                    if ($new !== $current) {
                        DB::table('populations')->where('id', $row->id)->update([$popColumn => $new]);
                        $updatedPlanets++;
                        $popDeltaSum += ($new - $current);
                    }
                }
            }

            $stabilityBefore = null;
            $stabilityAfter = null;
            if ($hasStability && $stabPct !== 0) {
                $stabilityBefore = (int) ($system->stability ?? 0);
                $stabilityAfter = max(0, min(100, $stabilityBefore + $stabPct));
                if ($stabilityAfter !== $stabilityBefore) {
                    $system->stability = $stabilityAfter;
                    $system->save();
                }
            }

            if ($popPct !== 0 || $stabPct !== 0) {
                $cmdId = $system->commander_id;
                $sysName = $system->name ?? ('#' . $system->id);
                $stabilityInfo = ($stabilityAfter !== null)
                    ? " (stability {$stabilityBefore} -> {$stabilityAfter})"
                    : '';
                Log::info("[Turn {$currentTurn}] Goods bonuses in system {$sysName} (commander {$cmdId}): population_growth_percent={$popPct} (planets_updated={$updatedPlanets}, total_delta={$popDeltaSum}), stability_percent_per_turn={$stabPct}{$stabilityInfo}");
            }
        }

        if (!$hasPopTable || !$popColumn) {
            Log::warning('PopulationStabilityManager: populations table/column not found. Skipped population growth this turn.');
        }
        if (!$hasStability) {
            Log::warning('PopulationStabilityManager: star_systems.stability column not found. Skipped stability adjustments this turn.');
        }
    }
}
