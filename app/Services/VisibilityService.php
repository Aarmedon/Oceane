<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\Fleet;
use App\Models\FleetShipStack;
use App\Models\Galaxy;
use App\Models\Sector;
use App\Models\StarSystem;
use App\Models\Planet;
use Illuminate\Support\Collection;

class VisibilityService
{
    /**
     * Build minimal map data for a commander in a given galaxy.
     * If $admin is true, returns full data without fog.
     */
    public function buildMapData(?Commander $commander, int $galaxyId, bool $admin = false): array
    {
        $galaxy = Galaxy::findOrFail($galaxyId);

        // Load systems in this galaxy
        $systems = StarSystem::query()
            ->whereHas('sector', function ($q) use ($galaxyId) {
                $q->where('galaxy_id', $galaxyId);
            })
            ->with(['sector:id,galaxy_id'])
            ->get(['id','sector_id','name','position_x','position_y','star_type','commander_id']);

        // Precompute planet-based ownership per system
        $systemIds = $systems->pluck('id')->all();
        $systemHasSelf = [];
        $systemHasColonized = [];
        if (!empty($systemIds)) {
            $planets = Planet::query()
                ->whereIn('star_system_id', $systemIds)
                ->get(['star_system_id','commander_id']);
            foreach ($planets as $p) {
                $sid = (int) $p->star_system_id;
                if (!is_null($p->commander_id)) {
                    $systemHasColonized[$sid] = true;
                    if ($commander && (int) $p->commander_id === (int) $commander->id) {
                        $systemHasSelf[$sid] = true;
                    }
                }
            }
        }

        // Own fleets in this galaxy (only if a commander is provided)
        $ownFleets = $commander
            ? $commander->fleets()
                ->where('galaxy_id', $galaxyId)
                ->get(['id','name','current_system_id','position_x','position_y','status','commander_id'])
            : collect();

        // Commander names for system owners (admin usage and self-labeling)
        $systemOwnerIds = $systems->pluck('commander_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->all();
        $commanderNameMap = [];
        if (!empty($systemOwnerIds)) {
            $commanderNameMap = Commander::query()
                ->whereIn('id', $systemOwnerIds)
                ->get(['id','name'])
                ->pluck('name', 'id')
                ->mapWithKeys(fn($name, $id) => [(int) $id => $name])
                ->all();
        }

        // Determine scan centers and range (for non-admin)
        $scanCenters = collect();
        $scanRange = (int) config('oceane.commander.base_scan_range', 10);

        if (!$admin && $commander) {
            // Scan centers from systems where the player owns at least one planet
            $ownedSystemPositions = $systems
                ->filter(fn($s) => !empty($systemHasSelf[(int) $s->id]))
                ->map(fn($s) => [$s->position_x, $s->position_y]);

            $scanCenters = $scanCenters->concat($ownedSystemPositions);

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
        $visibleSystems = $systems->map(function (StarSystem $s) use ($admin, $commander, $isWithinScan, $systemHasSelf, $systemHasColonized, $commanderNameMap) {
            $visible = $admin || $isWithinScan((int)$s->position_x, (int)$s->position_y);
            $sid = (int) $s->id;
            $hasSelf = $commander ? !empty($systemHasSelf[$sid]) : false;
            $hasColonized = !empty($systemHasColonized[$sid]);

            // Minimal info: 'self' if player owns any planet; else 'other' if any colonized planet exists; else 'neutral'
            $owner = $hasSelf ? 'self' : ($hasColonized ? 'other' : 'neutral');

            // Determine controlling commander (if any)
            $ownerCmdId = $s->commander_id ? (int) $s->commander_id : null;
            $ownerCmdName = $ownerCmdId && isset($commanderNameMap[$ownerCmdId]) ? $commanderNameMap[$ownerCmdId] : null;

            return [
                'id' => (int) $s->id,
                'name' => $s->name,
                'x' => (int) $s->position_x,
                'y' => (int) $s->position_y,
                'star_type' => (int) $s->star_type,
                'visible' => (bool) $visible,
                'owner' => $owner,
                // Expose owner commander only for admins; for players expose only for 'self'
                'owner_commander_id' => $admin ? $ownerCmdId : ($owner === 'self' && $commander ? (int) $commander->id : null),
                'owner_commander_name' => $admin ? $ownerCmdName : ($owner === 'self' && $commander ? (string) $commander->name : null),
            ];
        });

        // Filter systems for output: players see only visible systems; admin sees all
        $systemsForOutput = $admin
            ? $visibleSystems
            : $visibleSystems->filter(fn($s) => $s['visible']);

        // Safety: always include player's planet systems for non-admins
        if (!$admin && $commander) {
            $ownedAlways = $visibleSystems->filter(fn($s) => $s['owner'] === 'self');
            $systemsForOutput = $systemsForOutput->concat($ownedAlways)->unique('id')->values();
        }

        // Safety: always include the capital system for the commander (if present in this galaxy)
        if (!$admin && $commander && $commander->capital_system_id) {
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

        // Pre-compute fleet sizes (sum of ship counts) for all fleets present in this galaxy
        $fleetIds = $fleetsQuery->pluck('id')->map(fn($id) => (int) $id)->all();
        $sizesByFleet = [];
        if (!empty($fleetIds)) {
            $sizesByFleet = FleetShipStack::query()
                ->whereIn('fleet_id', $fleetIds)
                ->selectRaw('fleet_id, COALESCE(SUM(COALESCE(count_operational,0) + COALESCE(count_damaged,0)), 0) as size')
                ->groupBy('fleet_id')
                ->pluck('size', 'fleet_id')
                ->mapWithKeys(fn($size, $fid) => [(int) $fid => (int) $size])
                ->all();
        }

        // Ensure commander names include fleet owners (for admin display)
        $fleetOwnerIds = $fleetsQuery->pluck('commander_id')->filter()->unique()->map(fn($id) => (int) $id)->values()->all();
        if (!empty($fleetOwnerIds)) {
            $extraCmds = Commander::query()
                ->whereIn('id', $fleetOwnerIds)
                ->get(['id','name'])
                ->pluck('name','id')
                ->all();
            foreach ($extraCmds as $cid => $cname) {
                $commanderNameMap[(int) $cid] = $cname;
            }
        }

        $fleets = $fleetsQuery->filter(function (Fleet $f) use ($admin, $commander, $visibleSystemIds) {
            if ($admin) return true;
            if ($commander && (int)$f->commander_id === (int)$commander->id) return true;
            // Show enemy fleets only if their current system is visible
            return $f->current_system_id && in_array((int)$f->current_system_id, $visibleSystemIds, true);
        })->map(function (Fleet $f) use ($admin, $commander, $sizesByFleet, $commanderNameMap) {
            $owned = $commander ? ((int)$f->commander_id === (int)$commander->id) : false;
            return [
                'id' => (int) $f->id,
                'name' => $f->name,
                'system_id' => $f->current_system_id ? (int)$f->current_system_id : null,
                'x' => is_null($f->position_x) ? null : (int)$f->position_x,
                'y' => is_null($f->position_y) ? null : (int)$f->position_y,
                'status' => $f->status,
                // Keep 'owner' as string for compatibility
                'owner' => $owned ? 'self' : 'other',
                // Expose owner commander only for admins; for players expose only for 'self'
                'owner_commander_id' => ($admin || $owned) ? (int) $f->commander_id : null,
                'owner_commander_name' => $admin
                    ? (isset($commanderNameMap[(int)$f->commander_id]) ? (string) $commanderNameMap[(int)$f->commander_id] : null)
                    : ($owned && $commander ? (string) $commander->name : null),
                'size' => isset($sizesByFleet[(int) $f->id]) ? (int) $sizesByFleet[(int) $f->id] : 0,
            ];
        })->values();

        // Compute visible galaxies for coordinates mode selector
        $visibleGalaxies = [];
        if ($admin) {
            $visibleGalaxies = Galaxy::query()
                ->orderBy('id')
                ->get(['id','name'])
                ->map(fn($g) => ['id' => (int) $g->id, 'name' => (string) $g->name])
                ->values()
                ->all();
        } elseif ($commander) {
            // Capital system's galaxy (if any)
            $capGalaxyId = null;
            if ($commander->capital_system_id) {
                $cap = StarSystem::find($commander->capital_system_id);
                if ($cap && $cap->sector) {
                    $capGalaxyId = (int) $cap->sector->galaxy_id;
                }
            }
            // Galaxies where the commander owns at least one planet
            $planetGalaxyIds = StarSystem::query()
                ->whereHas('planets', function ($q) use ($commander) {
                    $q->where('commander_id', $commander->id);
                })
                ->with(['sector:id,galaxy_id'])
                ->get(['id','sector_id'])
                ->map(fn($s) => (int) $s->sector->galaxy_id)
                ->unique();
            // Galaxies where the commander has fleets
            $fleetGalaxyIds = $commander->fleets()
                ->pluck('galaxy_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique();
            $allGalaxyIds = collect([]);
            if (!is_null($capGalaxyId)) $allGalaxyIds = $allGalaxyIds->push($capGalaxyId);
            $allGalaxyIds = $allGalaxyIds->concat($planetGalaxyIds)->concat($fleetGalaxyIds)->unique()->values();
            if ($allGalaxyIds->isNotEmpty()) {
                $visibleGalaxies = Galaxy::query()
                    ->whereIn('id', $allGalaxyIds->all())
                    ->orderBy('id')
                    ->get(['id','name'])
                    ->map(fn($g) => ['id' => (int) $g->id, 'name' => (string) $g->name])
                    ->values()
                    ->all();
            } else {
                $visibleGalaxies = [];
            }
        }

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
            // Galaxies visible to the current commander (or all for admin)
            'visible_galaxies' => $visibleGalaxies,
        ];
    }
}
