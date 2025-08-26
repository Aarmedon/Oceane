<?php

namespace Tests\Feature;

use App\Models\Commander;
use App\Models\Fleet;
use App\Models\Galaxy;
use App\Models\Planet;
use App\Models\Race;
use App\Models\Sector;
use App\Models\StarSystem;
use App\Models\User;
use App\Models\ShipDesign;
use App\Models\FleetShipStack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GalaxyMapApiV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep default scan range; tests rely on small distances
        // config()->set('oceane.commander.base_scan_range', 10);
    }

    #[Test]
    public function player_sees_visible_entities_and_relations_v2(): void
    {
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $neutralVisible = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 105, 'position_y' => 100]);
        $otherVisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);
        $otherInvisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 400, 'position_y' => 400]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'capital_system_id' => $ownSystem->id,
        ]);
        $enemy = Commander::factory()->create();

        // Ownership via planets
        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $player->id]);
        Planet::factory()->create(['star_system_id' => $otherVisibleSystem->id, 'commander_id' => $enemy->id]);
        Planet::factory()->create(['star_system_id' => $otherInvisibleSystem->id, 'commander_id' => $enemy->id]);

        // Fleets
        $ownFleet = Fleet::factory()->create([
            'commander_id' => $player->id,
            'current_system_id' => $otherVisibleSystem->id,
            'position_x' => 108,
            'position_y' => 100,
            'galaxy_id' => $galaxy->id,
        ]);
        $enemyFleetVisible = Fleet::factory()->create([
            'commander_id' => $enemy->id,
            'current_system_id' => $otherVisibleSystem->id,
            'position_x' => 108,
            'position_y' => 100,
            'galaxy_id' => $galaxy->id,
        ]);
        $enemyFleetInvisible = Fleet::factory()->create([
            'commander_id' => $enemy->id,
            'current_system_id' => $otherInvisibleSystem->id,
            'position_x' => 400,
            'position_y' => 400,
            'galaxy_id' => $galaxy->id,
        ]);

        $resp = $this->actingAs($user)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
        ]));

        $resp->assertOk();
        $data = $resp->json();
        $this->assertEquals('player', $data['mode']);
        $this->assertEquals($galaxy->id, $data['galaxy']['id']);

        // Systems visibility
        $sysIds = collect($data['systems'])->pluck('id')->all();
        $this->assertContains($ownSystem->id, $sysIds);
        $this->assertContains($neutralVisible->id, $sysIds);
        $this->assertContains($otherVisibleSystem->id, $sysIds);
        $this->assertNotContains($otherInvisibleSystem->id, $sysIds);

        $systemsById = collect($data['systems'])->keyBy('id');
        // Own system is relation self and exposes owner id/name
        $this->assertEquals('self', $systemsById[$ownSystem->id]['owner']['relation']);
        $this->assertEquals($player->id, $systemsById[$ownSystem->id]['owner']['commander_id']);
        $this->assertNotNull($systemsById[$ownSystem->id]['owner']['commander_name']);
        // Neutral system
        $this->assertEquals('neutral', $systemsById[$neutralVisible->id]['owner']['relation']);
        $this->assertNull($systemsById[$neutralVisible->id]['owner']['commander_id']);
        // Visible enemy-owned system appears as unknown to player and does not expose owner id
        $this->assertEquals('unknown', $systemsById[$otherVisibleSystem->id]['owner']['relation']);
        $this->assertNull($systemsById[$otherVisibleSystem->id]['owner']['commander_id']);

        // Fleets visibility
        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertContains($ownFleet->id, $fleetIds);
        $this->assertContains($enemyFleetVisible->id, $fleetIds);
        $this->assertNotContains($enemyFleetInvisible->id, $fleetIds);

        $fleetsById = collect($data['fleets'])->keyBy('id');
        $this->assertEquals('self', $fleetsById[$ownFleet->id]['owner']['relation']);
        $this->assertEquals($player->id, $fleetsById[$ownFleet->id]['owner']['commander_id']);
        $this->assertEquals('unknown', $fleetsById[$enemyFleetVisible->id]['owner']['relation']);
        $this->assertNull($fleetsById[$enemyFleetVisible->id]['owner']['commander_id']);

        // Viewer block
        $this->assertEquals($player->id, $data['viewer']['effective_commander_id']);
        $this->assertFalse($data['viewer']['admin']);
    }

    #[Test]
    public function admin_sees_all_and_owner_names_for_fleets_v2(): void
    {
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        $s1 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $neutral = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 101, 'position_y' => 100]);
        $s2 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);
        $s3 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 400, 'position_y' => 400]);

        $player = Commander::factory()->create();
        $enemy = Commander::factory()->create();

        Planet::factory()->create(['star_system_id' => $s1->id, 'commander_id' => $player->id]);
        Planet::factory()->create(['star_system_id' => $s2->id, 'commander_id' => $enemy->id]);
        Planet::factory()->create(['star_system_id' => $s3->id, 'commander_id' => $enemy->id]);

        $f1 = Fleet::factory()->create(['commander_id' => $player->id, 'current_system_id' => $s2->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $f2 = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $s2->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $f3 = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $s3->id, 'position_x' => 400, 'position_y' => 400, 'galaxy_id' => $galaxy->id]);

        // GM user by configured email
        config()->set('oceane.admin.gamemasters_emails', ['gm@example.com']);
        $gm = User::factory()->create(['email' => 'gm@example.com']);

        $resp = $this->actingAs($gm)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
            'admin' => 1,
        ]));

        $resp->assertOk();
        $data = $resp->json();
        $this->assertEquals('admin', $data['mode']);

        $sysIds = collect($data['systems'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$s1->id, $neutral->id, $s2->id, $s3->id], $sysIds);

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$f1->id, $f2->id, $f3->id], $fleetIds);

        $fleetsById = collect($data['fleets'])->keyBy('id');
        // Admin exposes owner commander id and name for all fleets
        $this->assertEquals($player->id, $fleetsById[$f1->id]['owner']['commander_id']);
        $this->assertEquals($enemy->id, $fleetsById[$f2->id]['owner']['commander_id']);
        $this->assertEquals($enemy->id, $fleetsById[$f3->id]['owner']['commander_id']);
        $this->assertNotNull($fleetsById[$f1->id]['owner']['commander_name']);
    }

    #[Test]
    public function admin_impersonation_applies_fog_of_war_v2(): void
    {
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $neutralVisible = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 105, 'position_y' => 100]);
        $visibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);
        $invisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 400, 'position_y' => 400]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create(['user_id' => $user->id, 'race_id' => $race->id, 'capital_system_id' => $ownSystem->id]);
        $enemy = Commander::factory()->create();

        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $player->id]);
        Planet::factory()->create(['star_system_id' => $visibleSystem->id, 'commander_id' => $enemy->id]);
        Planet::factory()->create(['star_system_id' => $invisibleSystem->id, 'commander_id' => $enemy->id]);

        $ownFleet = Fleet::factory()->create(['commander_id' => $player->id, 'current_system_id' => $visibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $enemyFleetVis = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $visibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $enemyFleetInv = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $invisibleSystem->id, 'position_x' => 400, 'position_y' => 400, 'galaxy_id' => $galaxy->id]);

        // GM user by email
        config()->set('oceane.admin.gamemasters_emails', ['gm@example.com']);
        $gm = User::factory()->create(['email' => 'gm@example.com']);

        $resp = $this->actingAs($gm)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
            'admin' => 1,
            'as_commander_id' => $player->id,
        ]));

        $resp->assertOk();
        $data = $resp->json();
        $this->assertEquals('player', $data['mode']);

        $sysIds = collect($data['systems'])->pluck('id')->all();
        $this->assertContains($ownSystem->id, $sysIds);
        $this->assertContains($neutralVisible->id, $sysIds);
        $this->assertContains($visibleSystem->id, $sysIds);
        $this->assertNotContains($invisibleSystem->id, $sysIds);

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertContains($ownFleet->id, $fleetIds);
        $this->assertContains($enemyFleetVis->id, $fleetIds);
        $this->assertNotContains($enemyFleetInv->id, $fleetIds);
    }

    #[Test]
    public function filters_work_for_relations_and_sizes_v2(): void
    {
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $neutral1 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 101, 'position_y' => 100]);
        $neutral2 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 102, 'position_y' => 100]);
        $otherVisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create(['user_id' => $user->id, 'race_id' => $race->id, 'capital_system_id' => $ownSystem->id]);
        $enemy = Commander::factory()->create();

        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $player->id]);
        Planet::factory()->create(['star_system_id' => $otherVisibleSystem->id, 'commander_id' => $enemy->id]);

        $ownSmall = Fleet::factory()->create(['commander_id' => $player->id, 'current_system_id' => $otherVisibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $ownBig = Fleet::factory()->create(['commander_id' => $player->id, 'current_system_id' => $otherVisibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $enemyVis = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $otherVisibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);

        // Assign ship stacks to define sizes: 5 (small), 50 (big), 7 (enemy)
        $design = ShipDesign::factory()->create();
        FleetShipStack::create(['fleet_id' => $ownSmall->id, 'ship_design_id' => $design->id, 'count_operational' => 5, 'count_damaged' => 0, 'count_destroyed' => 0]);
        FleetShipStack::create(['fleet_id' => $ownBig->id, 'ship_design_id' => $design->id, 'count_operational' => 50, 'count_damaged' => 0, 'count_destroyed' => 0]);
        FleetShipStack::create(['fleet_id' => $enemyVis->id, 'ship_design_id' => $design->id, 'count_operational' => 7, 'count_damaged' => 0, 'count_destroyed' => 0]);

        $resp = $this->actingAs($user)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
            'systems_relations' => 'neutral',
            'fleets_relations' => 'self',
            'fleet_min_size' => 10,
        ]));

        $resp->assertOk();
        $data = $resp->json();

        $sysIds = collect($data['systems'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$neutral1->id, $neutral2->id], $sysIds);

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$ownBig->id], $fleetIds);
    }

    #[Test]
    public function visibility_block_is_present_for_player_and_null_for_admin_v2(): void
    {
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        $cap = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'capital_system_id' => $cap->id,
        ]);
        // Ensure at least one owned planet to match typical visibility setups
        Planet::factory()->create(['star_system_id' => $cap->id, 'commander_id' => $player->id]);

        // Player mode: visibility block should be present with non-empty cells and rects array
        $resp = $this->actingAs($user)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
        ]));
        $resp->assertOk();
        $data = $resp->json();
        $this->assertEquals('player', $data['mode']);
        $this->assertArrayHasKey('visibility', $data);
        $this->assertIsArray($data['visibility']);
        $this->assertArrayHasKey('cells', $data['visibility']);
        $this->assertIsArray($data['visibility']['cells']);
        $this->assertGreaterThan(0, count($data['visibility']['cells']));
        $this->assertArrayHasKey('rects', $data['visibility']);
        $this->assertIsArray($data['visibility']['rects']);

        // Admin mode (no impersonation): visibility should be null
        config()->set('oceane.admin.gamemasters_emails', ['gm@example.com']);
        $gm = User::factory()->create(['email' => 'gm@example.com']);
        $respAdmin = $this->actingAs($gm)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
            'admin' => 1,
        ]));
        $respAdmin->assertOk();
        $dataAdmin = $respAdmin->json();
        $this->assertEquals('admin', $dataAdmin['mode']);
        $this->assertNull($dataAdmin['visibility'] ?? null);
    }

    #[Test]
    public function visibility_cells_form_square_chebyshev_v2(): void
    {
        // Use a small deterministic scan range
        config()->set('oceane.commander.base_scan_range', 2);

        $galaxy = Galaxy::factory()->create(['size_x' => 200, 'size_y' => 200]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 200,
            'position_y_end' => 200,
        ]);

        $cx = 100; $cy = 100; $R = 2;
        $cap = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => $cx, 'position_y' => $cy]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'capital_system_id' => $cap->id,
        ]);
        // Own a planet on capital to ensure scan center
        Planet::factory()->create(['star_system_id' => $cap->id, 'commander_id' => $player->id]);

        $resp = $this->actingAs($user)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
        ]));
        $resp->assertOk();
        $data = $resp->json();

        $this->assertEquals('player', $data['mode']);
        $this->assertArrayHasKey('visibility', $data);
        $cells = $data['visibility']['cells'] ?? [];
        $this->assertGreaterThan(0, count($cells));

        // Convert to a fast lookup set of "x,y"
        $actualSet = [];
        foreach ($cells as $c) {
            $actualSet[$c[0] . ',' . $c[1]] = true;
        }

        // Expected Chebyshev square of side (2R+1)^2
        $expectedSet = [];
        for ($dy = -$R; $dy <= $R; $dy++) {
            for ($dx = -$R; $dx <= $R; $dx++) {
                $x = $cx + $dx; $y = $cy + $dy;
                $expectedSet[$x . ',' . $y] = true;
            }
        }

        $this->assertCount((2*$R + 1) * (2*$R + 1), $actualSet, 'cells count should match exact Chebyshev square');
        // All expected cells are present
        foreach ($expectedSet as $k => $_) {
            $this->assertArrayHasKey($k, $actualSet, "missing expected cell $k");
        }
        // Outside cell (R+1 along x) should not be present
        $outsideKey = ($cx + $R + 1) . ',' . $cy;
        $this->assertArrayNotHasKey($outsideKey, $actualSet, 'outside cell should not be visible');

        // Diagonal (cx+R, cy+R) MUST be present to confirm Chebyshev vs Euclidean
        $diagKey = ($cx + $R) . ',' . ($cy + $R);
        $this->assertArrayHasKey($diagKey, $actualSet, 'diagonal cell must be visible under Chebyshev');
    }

    #[Test]
    public function in_transit_enemy_fleet_visibility_chebyshev_v2(): void
    {
        config()->set('oceane.commander.base_scan_range', 2);

        $galaxy = Galaxy::factory()->create(['size_x' => 200, 'size_y' => 200]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 200,
            'position_y_end' => 200,
        ]);

        $cx = 100; $cy = 100; $R = 2;
        $cap = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => $cx, 'position_y' => $cy]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create(['user_id' => $user->id, 'race_id' => $race->id, 'capital_system_id' => $cap->id]);
        Planet::factory()->create(['star_system_id' => $cap->id, 'commander_id' => $player->id]);

        $enemy = Commander::factory()->create();
        // Enemy fleets in transit (no current_system_id): one inside scan, one outside
        $f_in = Fleet::factory()->create([
            'commander_id' => $enemy->id,
            'current_system_id' => null,
            'position_x' => $cx + $R,
            'position_y' => $cy,
            'galaxy_id' => $galaxy->id,
            'status' => 'moving',
        ]);
        $f_out = Fleet::factory()->create([
            'commander_id' => $enemy->id,
            'current_system_id' => null,
            'position_x' => $cx + $R + 1,
            'position_y' => $cy,
            'galaxy_id' => $galaxy->id,
            'status' => 'moving',
        ]);

        $resp = $this->actingAs($user)->getJson(route('game.api.v2.map', [
            'galaxy_id' => $galaxy->id,
        ]));
        $resp->assertOk();
        $data = $resp->json();

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertContains($f_in->id, $fleetIds, 'in-range transit fleet should be visible');
        $this->assertNotContains($f_out->id, $fleetIds, 'out-of-range transit fleet should not be visible');
    }
}
