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
use App\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GalaxyMapApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep default scan range unless overridden in a specific test
        // config()->set('oceane.commander.base_scan_range', 10);
    }

    #[Test]
    public function player_sees_only_visible_systems_and_fleets(): void
    {
        // Setup galaxy and sector
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create(['galaxy_id' => $galaxy->id, 'position_x_start' => 0, 'position_y_start' => 0, 'position_x_end' => 1000, 'position_y_end' => 1000]);

        // Systems: own (100,100), other visible (108,100), other invisible (400,400)
        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $otherVisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);
        $otherInvisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 400, 'position_y' => 400]);

        // Player and enemy commanders
        $user = User::factory()->create();
        $playerRace = Race::factory()->create();
        $playerCmdr = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $playerRace->id,
            'capital_system_id' => $ownSystem->id,
        ]);
        $enemyCmdr = Commander::factory()->create();

        // Colonization by planets (ownership for visibility service)
        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $playerCmdr->id]);
        Planet::factory()->create(['star_system_id' => $otherVisibleSystem->id, 'commander_id' => $enemyCmdr->id]);
        Planet::factory()->create(['star_system_id' => $otherInvisibleSystem->id, 'commander_id' => $enemyCmdr->id]);

        // Fleets: own in visible system, enemy in visible, enemy in invisible
        $ownFleet = Fleet::factory()->create([
            'commander_id' => $playerCmdr->id,
            'current_system_id' => $otherVisibleSystem->id,
            'position_x' => 108,
            'position_y' => 100,
            'galaxy_id' => $galaxy->id,
        ]);
        $enemyFleetVisible = Fleet::factory()->create([
            'commander_id' => $enemyCmdr->id,
            'current_system_id' => $otherVisibleSystem->id,
            'position_x' => 108,
            'position_y' => 100,
            'galaxy_id' => $galaxy->id,
        ]);
        $enemyFleetInvisible = Fleet::factory()->create([
            'commander_id' => $enemyCmdr->id,
            'current_system_id' => $otherInvisibleSystem->id,
            'position_x' => 400,
            'position_y' => 400,
            'galaxy_id' => $galaxy->id,
        ]);

        $resp = $this->actingAs($user)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
        ]));

        $resp->assertOk();
        $data = $resp->json();

        // Mode and galaxy
        $this->assertEquals('player', $data['mode']);
        $this->assertEquals($galaxy->id, $data['galaxy']['id']);

        // Systems: should include own and visible other, exclude invisible other
        $systemIds = collect($data['systems'])->pluck('id')->all();
        $this->assertContains($ownSystem->id, $systemIds);
        $this->assertContains($otherVisibleSystem->id, $systemIds);
        $this->assertNotContains($otherInvisibleSystem->id, $systemIds);

        // Owners and visibility flags
        $systemsById = collect($data['systems'])->keyBy('id');
        $this->assertEquals('self', $systemsById[$ownSystem->id]['owner']);
        $this->assertEquals('other', $systemsById[$otherVisibleSystem->id]['owner']);
        $this->assertTrue($systemsById[$ownSystem->id]['visible']);
        $this->assertTrue($systemsById[$otherVisibleSystem->id]['visible']);
        // owner commander exposed only for 'self' in player mode
        $this->assertEquals($playerCmdr->id, $systemsById[$ownSystem->id]['owner_commander_id']);
        $this->assertNull($systemsById[$otherVisibleSystem->id]['owner_commander_id']);

        // Fleets: own + enemy in visible system included; enemy in invisible excluded
        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertContains($ownFleet->id, $fleetIds);
        $this->assertContains($enemyFleetVisible->id, $fleetIds);
        $this->assertNotContains($enemyFleetInvisible->id, $fleetIds);

        $fleetsById = collect($data['fleets'])->keyBy('id');
        $this->assertEquals('self', $fleetsById[$ownFleet->id]['owner']);
        $this->assertEquals('other', $fleetsById[$enemyFleetVisible->id]['owner']);
        // owner commander exposed only for self (player mode)
        $this->assertEquals($playerCmdr->id, $fleetsById[$ownFleet->id]['owner_commander_id']);
        $this->assertNull($fleetsById[$enemyFleetVisible->id]['owner_commander_id']);

        // Visible galaxies should include at least the current one
        $vgIds = collect($data['visible_galaxies'])->pluck('id')->all();
        $this->assertContains($galaxy->id, $vgIds);
    }

    #[Test]
    public function admin_sees_all_systems_and_fleets(): void
    {
        // Setup galaxy and sector
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create(['galaxy_id' => $galaxy->id, 'position_x_start' => 0, 'position_y_start' => 0, 'position_x_end' => 1000, 'position_y_end' => 1000]);

        // Systems
        $s1 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $s2 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);
        $s3 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 400, 'position_y' => 400]);

        // Commanders
        $player = Commander::factory()->create();
        $enemy = Commander::factory()->create();

        // Colonization
        Planet::factory()->create(['star_system_id' => $s1->id, 'commander_id' => $player->id]);
        Planet::factory()->create(['star_system_id' => $s2->id, 'commander_id' => $enemy->id]);
        Planet::factory()->create(['star_system_id' => $s3->id, 'commander_id' => $enemy->id]);

        // Fleets
        $f1 = Fleet::factory()->create(['commander_id' => $player->id, 'current_system_id' => $s2->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $f2 = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $s2->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $f3 = Fleet::factory()->create(['commander_id' => $enemy->id, 'current_system_id' => $s3->id, 'position_x' => 400, 'position_y' => 400, 'galaxy_id' => $galaxy->id]);

        // GM user by email
        config()->set('oceane.admin.gamemasters_emails', ['gm@example.com']);
        $gm = User::factory()->create(['email' => 'gm@example.com']);

        $resp = $this->actingAs($gm)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
            'admin' => 1,
        ]));

        $resp->assertOk();
        $data = $resp->json();
        $this->assertEquals('admin', $data['mode']);

        // All systems and fleets included
        $sysIds = collect($data['systems'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$s1->id, $s2->id, $s3->id], $sysIds);

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$f1->id, $f2->id, $f3->id], $fleetIds);

        // Owner commander exposed for admin
        $fleetsById = collect($data['fleets'])->keyBy('id');
        $this->assertEquals($player->id, $fleetsById[$f1->id]['owner_commander_id']);
        $this->assertEquals($enemy->id, $fleetsById[$f2->id]['owner_commander_id']);
        $this->assertEquals($enemy->id, $fleetsById[$f3->id]['owner_commander_id']);

        // Visible galaxies should list all galaxies for admin; here only one exists
        $vgIds = collect($data['visible_galaxies'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$galaxy->id], $vgIds);
    }

    #[Test]
    public function admin_impersonation_applies_fog_of_war(): void
    {
        // Setup galaxy and sector
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create(['galaxy_id' => $galaxy->id, 'position_x_start' => 0, 'position_y_start' => 0, 'position_x_end' => 1000, 'position_y_end' => 1000]);

        // Systems: own (100,100), visible (108,100), invisible (400,400)
        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);
        $visibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 108, 'position_y' => 100]);
        $invisibleSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 400, 'position_y' => 400]);

        // Player commander (to impersonate) and enemy
        $user = User::factory()->create();
        $race = Race::factory()->create();
        $playerCmdr = Commander::factory()->create(['user_id' => $user->id, 'race_id' => $race->id, 'capital_system_id' => $ownSystem->id]);
        $enemyCmdr = Commander::factory()->create();

        // Planets for ownership
        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $playerCmdr->id]);
        Planet::factory()->create(['star_system_id' => $visibleSystem->id, 'commander_id' => $enemyCmdr->id]);
        Planet::factory()->create(['star_system_id' => $invisibleSystem->id, 'commander_id' => $enemyCmdr->id]);

        // Fleets
        $ownFleet = Fleet::factory()->create(['commander_id' => $playerCmdr->id, 'current_system_id' => $visibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $enemyFleetVis = Fleet::factory()->create(['commander_id' => $enemyCmdr->id, 'current_system_id' => $visibleSystem->id, 'position_x' => 108, 'position_y' => 100, 'galaxy_id' => $galaxy->id]);
        $enemyFleetInv = Fleet::factory()->create(['commander_id' => $enemyCmdr->id, 'current_system_id' => $invisibleSystem->id, 'position_x' => 400, 'position_y' => 400, 'galaxy_id' => $galaxy->id]);

        // GM user by email
        config()->set('oceane.admin.gamemasters_emails', ['gm@example.com']);
        $gm = User::factory()->create(['email' => 'gm@example.com']);

        // Admin impersonates player; should behave like player mode (fog applies)
        $resp = $this->actingAs($gm)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
            'admin' => 1,
            'as_commander_id' => $playerCmdr->id,
        ]));

        $resp->assertOk();
        $data = $resp->json();
        // Mode becomes player because renderAsAdmin=false when impersonating
        $this->assertEquals('player', $data['mode']);

        $sysIds = collect($data['systems'])->pluck('id')->all();
        $this->assertContains($ownSystem->id, $sysIds);
        $this->assertContains($visibleSystem->id, $sysIds);
        $this->assertNotContains($invisibleSystem->id, $sysIds);

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertContains($ownFleet->id, $fleetIds);
        $this->assertContains($enemyFleetVis->id, $fleetIds);
        $this->assertNotContains($enemyFleetInv->id, $fleetIds);
    }

    #[Test]
    public function sensors_technology_increases_visibility_range(): void
    {
        // Explicit base scan and bonus per level for determinism
        config()->set('oceane.commander.base_scan_range', 10);
        config()->set('oceane.technology.sensors_bonus_per_level', 1);

        // Galaxy and sector covering a large area
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        // Player capital/owned system as scan center
        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);

        // Targets at distances 11 and 12 from (100,100)
        $sys11 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 111, 'position_y' => 100]); // dist=11
        $sys12 = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 112, 'position_y' => 100]); // dist=12

        // Player commander and ownership via planet on own system
        $user = User::factory()->create();
        $race = Race::factory()->create();
        $player = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'capital_system_id' => $ownSystem->id,
        ]);
        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $player->id]);

        // No sensors technology: sys11 and sys12 should be invisible
        $resp0 = $this->actingAs($user)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
        ]));
        $resp0->assertOk();
        $ids0 = collect($resp0->json('systems'))->pluck('id')->all();
        $this->assertNotContains($sys11->id, $ids0);
        $this->assertNotContains($sys12->id, $ids0);

        // Create 'sensors' technology and attach at level 1 => range becomes 11
        $sensors = Technology::create([
            'name' => 'Capteurs',
            'description' => 'Améliore la portée de scan',
            'category' => Technology::CATEGORY_SENSORS,
            'base_research_cost' => 100,
            'level_multiplier' => 1.5,
            'max_level' => 10,
            'prerequisite_technology_id' => null,
            'prerequisite_level' => 1,
            'image_path' => null,
            'type' => Technology::TYPE_SIMPLE,
        ]);
        $player->technologies()->attach($sensors->id, ['level' => 1, 'research_progress' => 0, 'research_total' => 100]);

        $resp1 = $this->actingAs($user)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
        ]));
        $resp1->assertOk();
        $ids1 = collect($resp1->json('systems'))->pluck('id')->all();
        $this->assertContains($sys11->id, $ids1);
        $this->assertNotContains($sys12->id, $ids1);

        // Upgrade to level 2 => range becomes 12
        $player->technologies()->updateExistingPivot($sensors->id, ['level' => 2]);
        $resp2 = $this->actingAs($user)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
        ]));
        $resp2->assertOk();
        $ids2 = collect($resp2->json('systems'))->pluck('id')->all();
        $this->assertContains($sys11->id, $ids2);
        $this->assertContains($sys12->id, $ids2);
    }

    #[Test]
    public function in_transit_enemy_fleets_visibility_respects_scan_range(): void
    {
        // Deterministic scan range
        config()->set('oceane.commander.base_scan_range', 10);

        // Galaxy and a sector covering positions used below
        $galaxy = Galaxy::factory()->create(['size_x' => 1000, 'size_y' => 1000]);
        $sector = Sector::factory()->create([
            'galaxy_id' => $galaxy->id,
            'position_x_start' => 0,
            'position_y_start' => 0,
            'position_x_end' => 1000,
            'position_y_end' => 1000,
        ]);

        // Player owns a system at (100,100) -> scan center
        $ownSystem = StarSystem::factory()->create(['sector_id' => $sector->id, 'position_x' => 100, 'position_y' => 100]);

        $user = User::factory()->create();
        $race = \App\Models\Race::factory()->create();
        $player = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $race->id,
            'capital_system_id' => $ownSystem->id,
        ]);
        Planet::factory()->create(['star_system_id' => $ownSystem->id, 'commander_id' => $player->id]);

        // Enemy commander
        $enemy = Commander::factory()->create();

        // In-transit enemy fleet inside scan range: (108,100) -> dist=8 from (100,100)
        $enemyInside = Fleet::factory()->create([
            'commander_id' => $enemy->id,
            'current_system_id' => null,
            'destination_system_id' => null,
            'position_x' => 108,
            'position_y' => 100,
            'galaxy_id' => $galaxy->id,
            'status' => Fleet::STATUS_MOVING,
        ]);

        // In-transit enemy fleet outside scan range: (120,100) -> dist=20 from (100,100)
        $enemyOutside = Fleet::factory()->create([
            'commander_id' => $enemy->id,
            'current_system_id' => null,
            'destination_system_id' => null,
            'position_x' => 120,
            'position_y' => 100,
            'galaxy_id' => $galaxy->id,
            'status' => Fleet::STATUS_MOVING,
        ]);

        $resp = $this->actingAs($user)->getJson(route('game.api.map', [
            'galaxy_id' => $galaxy->id,
        ]));

        $resp->assertOk();
        $data = $resp->json();

        $fleetIds = collect($data['fleets'])->pluck('id')->all();
        $this->assertContains($enemyInside->id, $fleetIds, 'In-transit enemy fleet inside scan range should be visible');
        $this->assertNotContains($enemyOutside->id, $fleetIds, 'In-transit enemy fleet outside scan range should be hidden');
    }
}
