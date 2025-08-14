<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\Fleet;
use App\Models\Galaxy;
use App\Models\Sector;
use App\Models\StarSystem;
use Illuminate\Support\Collection;

class VisibilityService
{
    /**
     * Build minimal map data for a commander in a given galaxy.
     * If $admin is true, returns full data without fog.
     */
    public function buildMapData(Commander $commander, int $galaxyId, bool $admin = false): array
    {
        $galaxy = Galaxy::findOrFail($galaxyId);

        // Load systems in this galaxy
        $systems = StarSystem::query()
            ->whereHas('sector', function ($q) use ($galaxyId) {
                $q->where('galaxy_id', $galaxyId);
            })
            ->with(['sector:id,galaxy_id'])
            ->get(['id','sector_id','name','position_x','position_y','star_type','commander_id']);

        // Own fleets in this galaxy
        $ownFleets = $commander->fleets()
            ->where('galaxy_id', $galaxyId)
            ->get(['id','name','current_system_id','position_x','position_y','status','commander_id']);

        // Determine scan centers and range (for non-admin)
        $scanCenters = collect();
        $scanRange = (int) config('oceane.commander.base_scan_range', 10);

        if (!$admin) {
            // Owned systems in this galaxy
            $ownedSystems = $commander->starSystems()
                ->whereHas('sector', function ($q) use ($galaxyId) {
                    $q->where('galaxy_id', $galaxyId);
                })
                ->get(['id','position_x','position_y']);

            $scanCenters = $scanCenters->concat($ownedSystems->map(fn($s) => [$s->position_x, $s->position_y]));

            // Current system positions of own fleets
            $fleetSystems = $ownFleets->pluck('current_system_id')->filter()->unique();
            if ($fleetSystems->isNotEmpty()) {
                $fleetSystemsPositions = StarSystem::query()
                    ->whereIn('id', $fleetSystems->all())
                    ->get(['id','position_x','position_y'])
                    ->map(fn($s) => [$s->position_x, $s->position_y]);
                $scanCenters = $scanCenters->concat($fleetSystemsPositions);
            }

            // Technology bonus (if any)
            $scanTech = $commander->technologies()->where('category', 'sensors')->first();
            $scanBonus = $scanTech ? ((int) ($scanTech->pivot->level ?? 0)) : 0;
            $scanRange += $scanBonus;
        }

        // Helper to test visibility by Euclidean distance
        $isWithinScan = function (int $x, int $y) use ($scanCenters, $scanRange): bool {
            if ($scanCenters->isEmpty()) {
                return false;
            }
            foreach ($scanCenters as $c) {
                $dx = $x - $c[0];
                $dy = $y - $c[1];
                if (sqrt($dx * $dx + $dy * $dy) <= $scanRange) {
                    return true;
                }
            }
            return false;
        };

        // Compute visible systems
        $visibleSystems = $systems->map(function (StarSystem $s) use ($admin, $commander, $isWithinScan) {
            $visible = $admin || $isWithinScan((int)$s->position_x, (int)$s->position_y);
            $owned = (int)($s->commander_id ?? 0) === (int)$commander->id;

            // Minimal enemy info: hide commander_id unless admin or owner
            $owner = $owned ? 'self' : (($s->commander_id ? ($admin ? (int)$s->commander_id : 'other') : 'neutral'));

            return [
                'id' => (int) $s->id,
                'name' => $s->name,
                'x' => (int) $s->position_x,
                'y' => (int) $s->position_y,
                'star_type' => (int) $s->star_type,
                'visible' => (bool) $visible,
                'owner' => $owner,
            ];
        });

        // Filter systems for output: players see only visible systems; admin sees all
        $systemsForOutput = $admin
            ? $visibleSystems
            : $visibleSystems->filter(fn($s) => $s['visible']);

        // Safety: always include player's own systems for non-admins
        if (!$admin) {
            $ownedAlways = $visibleSystems->filter(fn($s) => $s['owner'] === 'self');
            $systemsForOutput = $systemsForOutput->concat($ownedAlways)->unique('id')->values();
        }

        // Safety: always include the capital system for the commander (if present in this galaxy)
        if (!$admin && $commander->capital_system_id) {
            $capId = (int) $commander->capital_system_id;
            $capitalEntry = $visibleSystems->firstWhere('id', $capId);
            if ($capitalEntry) {
                $systemsForOutput = $systemsForOutput->push($capitalEntry)->unique('id')->values();
            }
        }

        $visibleSystemIds = $systemsForOutput
            ->pluck('id')
            ->all();

        // Fleets: include all if admin, else include own + enemy fleets located in visible systems.
        $fleetsQuery = Fleet::query()->where('galaxy_id', $galaxyId)
            ->get(['id','name','current_system_id','position_x','position_y','status','commander_id']);

        $fleets = $fleetsQuery->filter(function (Fleet $f) use ($admin, $commander, $visibleSystemIds) {
            if ($admin) return true;
            if ((int)$f->commander_id === (int)$commander->id) return true;
            // Show enemy fleets only if their current system is visible
            return $f->current_system_id && in_array((int)$f->current_system_id, $visibleSystemIds, true);
        })->map(function (Fleet $f) use ($admin, $commander) {
            $owned = (int)$f->commander_id === (int)$commander->id;
            return [
                'id' => (int) $f->id,
                'name' => $f->name,
                'system_id' => $f->current_system_id ? (int)$f->current_system_id : null,
                'x' => is_null($f->position_x) ? null : (int)$f->position_x,
                'y' => is_null($f->position_y) ? null : (int)$f->position_y,
                'status' => $f->status,
                'owner' => $owned ? 'self' : ($admin ? (int)$f->commander_id : 'other'),
            ];
        })->values();

        return [
            'mode' => $admin ? 'admin' : 'player',
            'galaxy' => [
                'id' => (int) $galaxy->id,
                'name' => $galaxy->name,
                'size_x' => (int) $galaxy->size_x,
                'size_y' => (int) $galaxy->size_y,
            ],
            'systems' => $systemsForOutput->values(),
            'fleets' => $fleets,
            // Placeholder for future links/neighbors
            'links' => [],
        ];
    }
}
