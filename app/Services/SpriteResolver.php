<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\MapSprite;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SpriteResolver
{
    private ?array $rulesCache = null; // [scope => [MapSprite rows sorted by priority]]
    private ?int $neutralCommanderIdCache = null;

    private static function rulesCacheKey(): string
    {
        return 'map_sprites:rules';
    }

    private static function neutralCacheKey(): string
    {
        return 'map_sprites:neutral_commander_id';
    }

    /**
     * Clear instance and global caches. Call clearGlobalCache() when invalidating after admin edits.
     */
    public function clearCache(): void
    {
        $this->rulesCache = null;
        $this->neutralCommanderIdCache = null;
    }

    /**
     * Clear globally cached data so next request refreshes sprite rules and neutral commander ID.
     */
    public static function clearGlobalCache(): void
    {
        Cache::forget(self::rulesCacheKey());
        Cache::forget(self::neutralCacheKey());
    }

    /**
     * Determine relation category for a system from viewer perspective.
     * Categories: self, ally, neutral, unknown (enemy reserved for future diplomacy).
     */
    public function relationForSystem(
        ?int $viewerId,
        ?int $ownerCommanderId,
        bool $viewerHasPlanetInSystem,
        bool $isUnclaimed,
        array $viewerAllianceIds,
        array $alliancesByCommander
    ): string {
        // Viewer owns at least one planet in the system
        if ($viewerId && $viewerHasPlanetInSystem) {
            return 'self';
        }

        // Unclaimed systems are considered neutral (per spec)
        if ($isUnclaimed) {
            return 'neutral';
        }

        if ($ownerCommanderId !== null) {
            if ($viewerId && $ownerCommanderId === $viewerId) {
                return 'self';
            }
            $neutralId = $this->neutralCommanderId();
            if ($neutralId && $ownerCommanderId === $neutralId) {
                return 'neutral';
            }
            // Ally if they share at least one alliance
            $ownerAlliances = $alliancesByCommander[$ownerCommanderId] ?? [];
            if (!empty($viewerAllianceIds) && !empty($ownerAlliances)) {
                if (!empty(array_intersect($viewerAllianceIds, $ownerAlliances))) {
                    return 'ally';
                }
            }
            // TODO: enemy via diplomacy later
            return 'unknown';
        }

        // Fallback: unknown
        return 'unknown';
    }

    /**
     * Determine relation category for a fleet from viewer perspective.
     */
    public function relationForFleet(
        ?int $viewerId,
        ?int $ownerCommanderId,
        array $viewerAllianceIds,
        array $alliancesByCommander
    ): string {
        if ($ownerCommanderId === null) {
            return 'unknown';
        }
        if ($viewerId && $ownerCommanderId === $viewerId) {
            return 'self';
        }
        $neutralId = $this->neutralCommanderId();
        if ($neutralId && $ownerCommanderId === $neutralId) {
            return 'neutral';
        }
        $ownerAlliances = $alliancesByCommander[$ownerCommanderId] ?? [];
        if (!empty($viewerAllianceIds) && !empty($ownerAlliances)) {
            if (!empty(array_intersect($viewerAllianceIds, $ownerAlliances))) {
                return 'ally';
            }
        }
        // TODO: enemy via diplomacy later
        return 'unknown';
    }

    /**
     * Resolve a sprite URL given scope ('system'|'fleet'), ownerCategory, and optional size for fleets.
     */
    public function resolveSpriteUrl(string $scope, string $ownerCategory, ?int $size = null): ?string
    {
        $rules = $this->loadRules();
        $scopeRules = $rules[$scope] ?? [];
        if (empty($scopeRules)) return null;

        // Prefer exact owner category, then fall back to 'any'
        $candidates = array_values(array_filter($scopeRules, function ($r) use ($ownerCategory) {
            return $r->owner_category === $ownerCategory;
        }));
        if (empty($candidates)) {
            $candidates = array_values(array_filter($scopeRules, function ($r) {
                return $r->owner_category === 'any';
            }));
        }
        if (empty($candidates)) return null;

        // For fleets, also filter by size range
        if ($scope === 'fleet' && $size !== null) {
            $candidates = array_values(array_filter($candidates, function ($r) use ($size) {
                $minOk = is_null($r->size_min) || $size >= (int)$r->size_min;
                $maxOk = is_null($r->size_max) || $size <= (int)$r->size_max;
                return $minOk && $maxOk;
            }));
            if (empty($candidates)) return null;
        }

        // Already sorted by priority ASC in cache
        $rule = $candidates[0];
        $path = (string) $rule->image_path;
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        // Build a relative URL so the client keeps the current host:port
        $clean = ltrim($path, '/');
        if (str_starts_with($clean, 'storage/')) {
            return '/'.$clean;
        }
        return '/storage/'.$clean;
    }

    private function loadRules(): array
    {
        if ($this->rulesCache !== null) return $this->rulesCache;
        $cacheKey = self::rulesCacheKey();
        $byScope = Cache::remember($cacheKey, 3600, function () {
            $all = MapSprite::query()
                ->where('is_active', true)
                ->orderBy('scope')
                ->orderBy('priority')
                ->get();
            $scoped = [
                'system' => [],
                'fleet' => [],
            ];
            foreach ($all as $row) {
                $scoped[$row->scope][] = $row;
            }
            return $scoped;
        });
        $this->rulesCache = $byScope;
        return $this->rulesCache;
    }

    private function neutralCommanderId(): ?int
    {
        if ($this->neutralCommanderIdCache !== null) return $this->neutralCommanderIdCache;
        $name = (string) config('oceane.enrollment.neutral.commander_name', 'Neutral');
        $cacheKey = self::neutralCacheKey();
        $cached = Cache::remember($cacheKey, 3600, function () use ($name) {
            $foundId = Commander::where('name', $name)->value('id');
            return $foundId ? (int) $foundId : null;
        });
        $this->neutralCommanderIdCache = $cached;
        return $this->neutralCommanderIdCache;
    }
}
