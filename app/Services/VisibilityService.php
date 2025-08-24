<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\Fleet;
use App\Models\FleetShipStack;
use App\Models\Galaxy;
use App\Models\Sector;
use App\Models\StarSystem;
use App\Models\Planet;
use App\Models\Technology;
use Illuminate\Support\Facades\DB;



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
            ->get(['id','sector_id','name','position_x','position_y','star_type','map_icon_path','commander_id']);

        // Position lookup map for fast heading computation
        $systemPosById = [];
        foreach ($systems as $s) {
            $systemPosById[(int) $s->id] = [(int) $s->position_x, (int) $s->position_y];
        }

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

        // Also consider StarSystem-level ownership as a fallback (legacy data)
        // If star_systems.commander_id is set, treat the system as colonized for labeling
        // IMPORTANT: do NOT mark as self from this field; detection/self comes from planets only
        foreach ($systems as $s) {
            $sid = (int) $s->id;
            $ownerCid = is_null($s->commander_id) ? null : (int) $s->commander_id;
            if (!is_null($ownerCid)) {
                $systemHasColonized[$sid] = true;
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
            $cmdRows = Commander::query()
                ->whereIn('id', $systemOwnerIds)
                ->get(['id','name','fleet_icon_path']);
            $commanderNameMap = $cmdRows
                ->pluck('name', 'id')
                ->mapWithKeys(fn($name, $id) => [(int) $id => $name])
                ->all();
        }

        // Removed sprite resolver and alliance-based relation computation

        // Determine scan centers and range (for non-admin)
        $scanCenters = collect();
        $scanRange = (int) config('oceane.commander.base_scan_range', 10);
        $sensorsBonusPerLevel = (int) config('oceane.technology.sensors_bonus_per_level', 1);

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
            $scanTech = $commander->technologies()->where('category', Technology::CATEGORY_SENSORS)->first();
            $scanLevel = $scanTech ? ((int) ($scanTech->pivot->level ?? 0)) : 0;
            $scanRange += $scanLevel * $sensorsBonusPerLevel;

            // Always include capital as a scan center if it is in this galaxy
            if ($commander->capital_system_id) {
                $capSys = $systems->firstWhere('id', $commander->capital_system_id);
                if ($capSys) {
                    $scanCenters = $scanCenters->push([$capSys->position_x, $capSys->position_y]);
                }
            }
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

        // Build system entries with visibility and ownership
        $systemsForOutput = collect();
        foreach ($systems as $s) {
            $sid = (int) $s->id;
            $x = (int) $s->position_x;
            $y = (int) $s->position_y;
            $hasSelf = $commander ? !empty($systemHasSelf[$sid]) : false;
            $hasColonized = !empty($systemHasColonized[$sid]);
            $owner = $hasSelf ? 'self' : ($hasColonized ? 'other' : 'neutral');
            $visible = $admin || $isWithinScan($x, $y) || $hasSelf;
            if ($admin || $visible) {
                $systemsForOutput->push([
                    'id' => $sid,
                    'name' => (string) $s->name,
                    'x' => $x,
                    'y' => $y,
                    'star_type' => (int) $s->star_type,
                    'visible' => (bool) ($admin ? true : $visible),
                    'owner' => $owner,
                    // Expose owner commander only for self in player mode (tests rely on this). For admin we keep same rule for simplicity.
                    'owner_commander_id' => ($hasSelf && $commander) ? (int) $commander->id : null,
                    'owner_commander_name' => ($hasSelf && $commander) ? (string) $commander->name : null,
                    'sprite_url' => null,
                ]);
            }
        }
        // Safety: always include the capital system for the commander (if present in this galaxy)
        if (!$admin && $commander && $commander->capital_system_id) {
            $capId = (int) $commander->capital_system_id;
            $already = $systemsForOutput->firstWhere('id', $capId);
            if (!$already) {
                $cap = $systems->firstWhere('id', $capId);
                if ($cap) {
                    $systemsForOutput->push([
                        'id' => (int) $cap->id,
                        'name' => (string) $cap->name,
                        'x' => (int) $cap->position_x,
                        'y' => (int) $cap->position_y,
                        'star_type' => (int) $cap->star_type,
                        'visible' => true,
                        'owner' => 'self',
                        'owner_commander_id' => (int) $commander->id,
                        'owner_commander_name' => (string) $commander->name,
                        'sprite_url' => null,
                    ]);
                }
            }
        }
        $systemsForOutput = $systemsForOutput->unique('id')->values();
        $visibleSystemIds = $systemsForOutput->pluck('id')->all();

        // Fleets: include all if admin, else include own + enemy fleets located in visible systems.
        $fleetsQuery = Fleet::query()->where('galaxy_id', $galaxyId)
            ->get(['id','name','current_system_id','destination_system_id','position_x','position_y','status','commander_id']);

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
                ->get(['id','name','fleet_icon_path']);
            foreach ($extraCmds as $row) {
                $cid = (int) $row->id;
                $commanderNameMap[$cid] = (string) $row->name;
            }
        }

        // Removed alliance mapping extension; relation/sprite no longer computed

        $fleets = $fleetsQuery->filter(function (Fleet $f) use ($admin, $commander, $visibleSystemIds, $isWithinScan) {
            if ($admin) return true;
            if ($commander && (int)$f->commander_id === (int)$commander->id) return true;
            // Enemy fleets:
            // - If docked (current_system_id set): visible only if their system is visible
            if ($f->current_system_id) {
                return in_array((int)$f->current_system_id, $visibleSystemIds, true);
            }
            // - If in transit (no current system): visible if their coordinates are within scan range
            if (!is_null($f->position_x) && !is_null($f->position_y)) {
                return $isWithinScan((int)$f->position_x, (int)$f->position_y);
            }
            return false;
        })->map(function (Fleet $f) use ($admin, $commander, $sizesByFleet, $commanderNameMap, $systemPosById) {
            $owned = $commander ? ((int)$f->commander_id === (int)$commander->id) : false;

            // Compute heading degrees (0 = +X/right, increasing clockwise due to Y-down coordinates)
            $headingDeg = null;
            $destId = $f->destination_system_id ? (int) $f->destination_system_id : null;
            if ($f->status === Fleet::STATUS_MOVING && $destId && isset($systemPosById[$destId])) {
                $originX = !is_null($f->position_x) ? (int) $f->position_x : null;
                $originY = !is_null($f->position_y) ? (int) $f->position_y : null;
                if ($originX === null || $originY === null) {
                    $curId = $f->current_system_id ? (int) $f->current_system_id : null;
                    if ($curId && isset($systemPosById[$curId])) {
                        $originX = $systemPosById[$curId][0];
                        $originY = $systemPosById[$curId][1];
                    }
                }
                $destPos = $systemPosById[$destId] ?? null;
                if ($destPos && $originX !== null && $originY !== null) {
                    $dx = $destPos[0] - $originX;
                    $dy = $destPos[1] - $originY;
                    if ($dx != 0 || $dy != 0) {
                        $headingDeg = rad2deg(atan2($dy, $dx));
                    }
                }
            }

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
                // Rotation support for frontend
                'heading' => $headingDeg,
                'destination_system_id' => ($admin || $owned) ? $destId : null,
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

        // Sectors for this galaxy (used for grid overlay and labels on the client)
        $sectors = Sector::query()
            ->where('galaxy_id', $galaxyId)
            ->orderBy('id')
            ->get(['id','name','position_x_start','position_y_start','position_x_end','position_y_end'])
            ->map(fn($s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'start_x' => (int) $s->position_x_start,
                'start_y' => (int) $s->position_y_start,
                'end_x' => (int) $s->position_x_end,
                'end_y' => (int) $s->position_y_end,
            ])
            ->values()
            ->all();

        // Commander info (optional): expose capital coordinates for centering
        $commanderInfo = null;
        if ($commander) {
            $commanderInfo = [
                'id' => (int) $commander->id,
                'name' => (string) $commander->name,
                'capital_x' => null,
                'capital_y' => null,
            ];
            if ($commander->capital_system_id) {
                $capSys = StarSystem::find($commander->capital_system_id);
                if ($capSys) {
                    $commanderInfo['capital_x'] = (int) $capSys->position_x;
                    $commanderInfo['capital_y'] = (int) $capSys->position_y;
                }
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
            // Sectors grid for the current galaxy
            'sectors' => $sectors,
            // Optional commander info (for centering on capital)
            'commander' => $commanderInfo,
        ];
    }
}
