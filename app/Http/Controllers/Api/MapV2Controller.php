<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MapV2Request;
use App\Models\Commander;
use App\Models\Fleet;
use App\Models\Galaxy;
use App\Models\StarSystem;
use App\Services\SpriteResolver;
use App\Services\VisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MapV2Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(
        MapV2Request $request,
        VisibilityService $visibilityService,
        SpriteResolver $spriteResolver
    ): JsonResponse {
        $user = Auth::user();
        $baseCommander = $user?->commanders()->first();

        // Debug logging toggle via ?debug=1 (disabled in production)
        $debugParam = (bool) $request->boolean('debug', false);
        $debug = !app()->environment('production') && $debugParam;
        $reqId = (string) Str::uuid();
        if ($debug) {
            Log::info("[mapApiV2][{$reqId}] start", [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'params' => $request->only([
                    'galaxy_id','admin','as_commander_id','debug',
                    'systems_relations','fleets_relations','fleet_min_size','fleet_max_size','fleet_owner_id'
                ]),
            ]);
        }

        // Admin eligibility (MJ) and impersonation
        $gmEmails = array_map('strtolower', (array) config('oceane.admin.gamemasters_emails', []));
        $isGameMaster = (bool) ($user->is_admin ?? false) || in_array(strtolower((string) $user->email), $gmEmails, true);
        $adminRequested = (bool) $request->boolean('admin', false);
        $adminAllowed = $isGameMaster && $adminRequested;

        $commander = $baseCommander;
        $asCommanderId = null;
        if ($adminAllowed && $request->filled('as_commander_id')) {
            $asCommanderId = (int) $request->input('as_commander_id');
            $asCommander = Commander::find($asCommanderId);
            if ($asCommander) {
                $commander = $asCommander;
            }
        }

        // Resolve target galaxy
        $galaxyId = (int) $request->input('galaxy_id', 0);
        if (!$galaxyId) {
            if ($commander) {
                $cap = StarSystem::find($commander->capital_system_id);
                $galaxyId = $cap && $cap->sector ? (int) $cap->sector->galaxy_id : 1;
            } else {
                $firstGalaxyId = Galaxy::query()->orderBy('id')->value('id');
                $galaxyId = $firstGalaxyId ? (int) $firstGalaxyId : 1;
            }
        }

        // Admin sees full; impersonation uses player fog
        $renderAsAdmin = $adminAllowed && !$asCommanderId;

        $t0 = microtime(true);
        $v1 = $visibilityService->buildMapData($commander, $galaxyId, $renderAsAdmin);
        $dt = microtime(true) - $t0;
        if ($debug) {
            Log::info("[mapApiV2][{$reqId}] built_data", [
                'mode' => $v1['mode'] ?? null,
                'counts' => [
                    'systems' => is_countable($v1['systems'] ?? null) ? count($v1['systems']) : 0,
                    'fleets' => is_countable($v1['fleets'] ?? null) ? count($v1['fleets']) : 0,
                ],
                'render_as_admin' => $renderAsAdmin,
                'duration_ms' => (int) round($dt * 1000),
            ]);
        }

        // Preload ownership for relation computation
        $systemIds = collect($v1['systems'] ?? [])->pluck('id')->all();
        $fleetIds = collect($v1['fleets'] ?? [])->pluck('id')->all();
        $systemOwnerById = [];
        if (!empty($systemIds)) {
            $systemOwnerById = StarSystem::query()
                ->whereIn('id', $systemIds)
                ->pluck('commander_id', 'id')
                ->mapWithKeys(fn($cid, $sid) => [(int) $sid => is_null($cid) ? null : (int) $cid])
                ->all();
        }
        $fleetOwnerById = [];
        if (!empty($fleetIds)) {
            $fleetOwnerById = Fleet::query()
                ->whereIn('id', $fleetIds)
                ->pluck('commander_id', 'id')
                ->mapWithKeys(fn($cid, $fid) => [(int) $fid => is_null($cid) ? null : (int) $cid])
                ->all();
        }

        // Alliances: gather viewer + all owners in play
        $viewerId = $commander?->id ? (int) $commander->id : null;
        $ownerCmdIds = collect(array_merge(
            array_values(array_filter($systemOwnerById, fn($v) => !is_null($v))),
            array_values(array_filter($fleetOwnerById, fn($v) => !is_null($v)))
        ))->unique()->values()->all();
        $allCmdIds = $viewerId ? array_unique(array_merge([$viewerId], $ownerCmdIds)) : $ownerCmdIds;

        $alliancesByCommander = [];
        $viewerAllianceIds = [];
        if (!empty($allCmdIds)) {
            $cmdRows = Commander::query()
                ->whereIn('id', $allCmdIds)
                ->with(['alliances:id'])
                ->get(['id']);
            foreach ($cmdRows as $c) {
                $ids = $c->alliances->pluck('id')->map(fn($id) => (int) $id)->values()->all();
                $alliancesByCommander[(int) $c->id] = $ids;
                if ($viewerId && (int) $c->id === $viewerId) {
                    $viewerAllianceIds = $ids;
                }
            }
        }

        // Prefetch commander names for involved owners to avoid N+1
        $ownerNameById = [];
        if (!empty($allCmdIds)) {
            $ownerNameById = Commander::query()->whereIn('id', $allCmdIds)->pluck('name', 'id')
                ->mapWithKeys(fn($name, $id) => [(int) $id => (string) $name])->all();
        }

        // Transform systems
        $systemsV2 = [];
        foreach ($v1['systems'] as $s) {
            $sid = (int) $s['id'];
            $ownerId = $systemOwnerById[$sid] ?? null;
            $viewerHasPlanet = ($s['owner'] === 'self');
            $isUnclaimed = ($s['owner'] === 'neutral');
            $relation = $spriteResolver->relationForSystem(
                $viewerId,
                $ownerId,
                $viewerHasPlanet,
                $isUnclaimed,
                $viewerAllianceIds,
                $alliancesByCommander
            );
            $spriteUrl = $spriteResolver->resolveSpriteUrl('system', $relation);

            // Owner info visibility: admin sees all, player sees only self
            $exposeOwner = $renderAsAdmin || $viewerHasPlanet;
            // If viewer owns a planet, expose viewer as owner even if star_systems.commander_id is null
            if ($viewerHasPlanet && $viewerId) {
                $ownerId = $viewerId;
            }
            $ownerName = ($exposeOwner && $ownerId && isset($ownerNameById[$ownerId])) ? $ownerNameById[$ownerId] : null;

            $systemsV2[] = [
                'id' => $sid,
                'name' => (string) $s['name'],
                'x' => (int) $s['x'],
                'y' => (int) $s['y'],
                'star_type' => (int) $s['star_type'],
                'visible' => (bool) $s['visible'],
                'owner' => [
                    'relation' => $relation,
                    'commander_id' => $exposeOwner && $ownerId ? (int) $ownerId : null,
                    'commander_name' => $exposeOwner && $ownerName ? (string) $ownerName : null,
                ],
                'sprites' => [
                    'map' => $spriteUrl,
                ],
            ];
        }

        // Transform fleets
        $fleetsV2 = [];
        foreach ($v1['fleets'] as $f) {
            $fid = (int) $f['id'];
            $ownerId = $fleetOwnerById[$fid] ?? ($f['owner_commander_id'] ?? null);
            $ownerId = is_null($ownerId) ? null : (int) $ownerId;
            $relation = $spriteResolver->relationForFleet(
                $viewerId,
                $ownerId,
                $viewerAllianceIds,
                $alliancesByCommander
            );
            $spriteUrl = $spriteResolver->resolveSpriteUrl('fleet', $relation, (int) $f['size']);

            $owned = ($viewerId && $ownerId === $viewerId);
            $exposeOwner = $renderAsAdmin || $owned;
            $ownerName = ($exposeOwner && $ownerId && isset($ownerNameById[$ownerId])) ? $ownerNameById[$ownerId] : null;

            $fleetsV2[] = [
                'id' => $fid,
                'name' => (string) $f['name'],
                'system_id' => is_null($f['system_id']) ? null : (int) $f['system_id'],
                'x' => is_null($f['x']) ? null : (int) $f['x'],
                'y' => is_null($f['y']) ? null : (int) $f['y'],
                'status' => (string) $f['status'],
                'heading' => $f['heading'],
                'size' => (int) $f['size'],
                'owner' => [
                    'relation' => $relation,
                    'commander_id' => $exposeOwner && $ownerId ? (int) $ownerId : null,
                    'commander_name' => $exposeOwner && $ownerName ? (string) $ownerName : null,
                ],
                'destination_system_id' => $f['destination_system_id'] ?? null,
                'sprites' => [
                    'map' => $spriteUrl,
                ],
            ];
        }

        // Apply optional filters
        $filters = $request->validatedFilters();
        if (!empty($filters['systems_rel'])) {
            $keep = array_flip($filters['systems_rel']);
            $systemsV2 = array_values(array_filter($systemsV2, function ($s) use ($keep) {
                return isset($keep[$s['owner']['relation']]);
            }));
        }
        if (!empty($filters['fleets_rel'])) {
            $keep = array_flip($filters['fleets_rel']);
            $fleetsV2 = array_values(array_filter($fleetsV2, function ($f) use ($keep) {
                return isset($keep[$f['owner']['relation']]);
            }));
        }
        if (!is_null($filters['fleet_owner_id'] ?? null)) {
            $oid = (int) $filters['fleet_owner_id'];
            $fleetsV2 = array_values(array_filter($fleetsV2, function ($f) use ($oid) {
                return (int) ($f['owner']['commander_id'] ?? -1) === $oid;
            }));
        }
        if (!is_null($filters['fleet_min_size'] ?? null)) {
            $min = (int) $filters['fleet_min_size'];
            $fleetsV2 = array_values(array_filter($fleetsV2, fn($f) => (int) $f['size'] >= $min));
        }
        if (!is_null($filters['fleet_max_size'] ?? null)) {
            $max = (int) $filters['fleet_max_size'];
            $fleetsV2 = array_values(array_filter($fleetsV2, fn($f) => (int) $f['size'] <= $max));
        }

        $response = [
            'mode' => $renderAsAdmin ? 'admin' : 'player',
            'galaxy' => $v1['galaxy'],
            'viewer' => [
                'base_commander_id' => $baseCommander?->id ? (int) $baseCommander->id : null,
                'as_commander_id' => $asCommanderId,
                'effective_commander_id' => $commander?->id ? (int) $commander->id : null,
                'admin' => (bool) $renderAsAdmin,
            ],
            'systems' => $systemsV2,
            'fleets' => $fleetsV2,
            'links' => $v1['links'] ?? [],
            'visible_galaxies' => $v1['visible_galaxies'] ?? [],
            'sectors' => $v1['sectors'] ?? [],
            'commander' => $v1['commander'] ?? null,
            'visibility' => $v1['visibility'] ?? null,
            'filters' => [
                'systems_relations' => $filters['systems_rel'] ?? null,
                'fleets_relations' => $filters['fleets_rel'] ?? null,
                'fleet_min_size' => $filters['fleet_min_size'] ?? null,
                'fleet_max_size' => $filters['fleet_max_size'] ?? null,
                'fleet_owner_id' => $filters['fleet_owner_id'] ?? null,
            ],
        ];

        if ($debug) {
            Log::info("[mapApiV2][{$reqId}] end", [
                'systems_after_filters' => count($response['systems']),
                'fleets_after_filters' => count($response['fleets']),
            ]);
        }

        return response()->json($response);
    }
}
