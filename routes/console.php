<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Ship;
use App\Models\ShipDesign;
use App\Models\FleetShipStack;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Agréger les vaisseaux existants dans fleet_ship_stacks
Artisan::command('oceane:backfill-stacks {--delete-rows : Supprimer les lignes ships stackables après backfill (recommandé en stack_only)} {--dry-run : Simuler sans écrire}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $deleteRows = (bool) $this->option('delete-rows');
    $mode = config('oceane.fleet.stacking_mode', 'stack_and_row');

    $stackableIds = ShipDesign::where('is_stackable', true)->pluck('id');
    if ($stackableIds->isEmpty()) {
        $this->info('Aucun design stackable trouvé.');
        return 0;
    }

    $this->info('Début backfill des stacks...');
    $totalGroups = 0;
    $totalInserted = 0;

    // Agrégation côté DB pour efficacité
    $groups = Ship::query()
        ->select('fleet_id', 'ship_design_id',
            DB::raw("SUM(CASE WHEN status = 'operational' THEN 1 ELSE 0 END) as cnt_operational"),
            DB::raw("SUM(CASE WHEN status in ('damaged','critical','repairing') THEN 1 ELSE 0 END) as cnt_damaged"),
            DB::raw("SUM(CASE WHEN status = 'destroyed' THEN 1 ELSE 0 END) as cnt_destroyed")
        )
        ->whereIn('ship_design_id', $stackableIds)
        ->groupBy('fleet_id', 'ship_design_id')
        ->orderBy('fleet_id')
        ->orderBy('ship_design_id')
        ->get();

    DB::beginTransaction();
    try {
        foreach ($groups as $g) {
            $totalGroups++;
            if (!$dryRun) {
                $stack = FleetShipStack::updateOrCreate(
                    [
                        'fleet_id' => $g->fleet_id,
                        'ship_design_id' => $g->ship_design_id,
                    ],
                    [
                        'count_operational' => (int) $g->cnt_operational,
                        'count_damaged' => (int) $g->cnt_damaged,
                        'count_destroyed' => (int) $g->cnt_destroyed,
                    ]
                );
                $totalInserted++;
            }
        }

        // Optionnel: suppression des lignes ships pour les designs stackables (nettoyage)
        if ($deleteRows && !$dryRun) {
            if ($mode !== 'stack_only') {
                $this->warn("Suppression des lignes 'ships' demandée mais stacking_mode != 'stack_only'. Continuer quand même...");
            }
            $deleted = Ship::whereIn('ship_design_id', $stackableIds)->delete();
            $this->info("Lignes 'ships' supprimées pour designs stackables: {$deleted}");
        }

        if ($dryRun) {
            DB::rollBack();
            $this->info("Dry-run: rollback des écritures.");
        } else {
            DB::commit();
        }

        $this->info("Backfill terminé. Groupes traités: {$totalGroups}. Stacks upsertés: {$totalInserted}.");
    } catch (\Throwable $e) {
        DB::rollBack();
        $this->error('Erreur pendant le backfill: ' . $e->getMessage());
        return 1;
    }

    return 0;
})->purpose("Agréger les vaisseaux existants par flotte/design dans fleet_ship_stacks");
