<?php

namespace Tests\Unit;

use App\Models\Commander;
use App\Models\GameState;
use App\Models\Planet;
use App\Models\Population;
use App\Models\StarSystem;
use App\Services\EconomyManager;
use App\Services\PopulationStabilityManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PopulationStabilityManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure base GameState exists (some services expect it in app lifecycle)
        GameState::create([
            'current_turn' => 1,
            'last_resolved_at' => null,
            'next_turn_at' => now()->addHours(config('oceane.game.hours_between_turns', 24)),
        ]);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_applies_population_growth_and_stability_with_bonuses(): void
    {
        // Arrange: a commander and a controlled system
        $commander = Commander::factory()->create();
        $system = StarSystem::factory()->create([
            'is_controlled' => true,
            'commander_id' => $commander->id,
        ]);
        // Ensure baseline stability
        $system->stability = 50;
        $system->save();

        // Two colonized planets with populations
        $p1 = Planet::factory()->create([
            'star_system_id' => $system->id,
            'is_colonized' => true,
            'commander_id' => $commander->id,
        ]);
        $p2 = Planet::factory()->create([
            'star_system_id' => $system->id,
            'is_colonized' => true,
            'commander_id' => $commander->id,
        ]);
        Population::create(['planet_id' => $p1->id, 'population' => 1000]);
        Population::create(['planet_id' => $p2->id, 'population' => 2000]);

        // Mock EconomyManager to return active bonuses (sum of goods with stock >= 100)
        $mock = \Mockery::mock(EconomyManager::class);
        $mock->shouldReceive('getActiveBonusesForSystem')
            ->andReturn([
                'population_growth_percent' => 15,
                'stability_percent_per_turn' => 1,
            ]);
        $this->app->instance(EconomyManager::class, $mock);

        Event::fake([MessageLogged::class]);

        // Act
        app(PopulationStabilityManager::class)->applyPopulationAndStabilityForTurn(10);

        // Assert populations updated (floor applied)
        $this->assertDatabaseHas('populations', ['planet_id' => $p1->id, 'population' => 1150]);
        $this->assertDatabaseHas('populations', ['planet_id' => $p2->id, 'population' => 2300]);

        // Assert stability updated and clamped in [0,100]
        $system->refresh();
        $this->assertEquals(51, $system->stability);

        // Assert informative log emitted with details
        Event::assertDispatched(MessageLogged::class, function ($event) use ($system) {
            return $event->level === 'info'
                && is_string($event->message)
                && str_contains($event->message, 'Goods bonuses in system')
                && str_contains($event->message, (string)($system->name ?? ('#'.$system->id)))
                && str_contains($event->message, 'population_growth_percent=15')
                && str_contains($event->message, 'stability_percent_per_turn=1')
                && str_contains($event->message, 'planets_updated=2')
                && str_contains($event->message, 'total_delta=450');
        });
    }

    public function test_stability_is_clamped_between_0_and_100(): void
    {
        $commander = Commander::factory()->create();

        // Upper clamp
        $systemHigh = StarSystem::factory()->create([
            'is_controlled' => true,
            'commander_id' => $commander->id,
        ]);
        $systemHigh->stability = 99;
        $systemHigh->save();

        // Lower clamp
        $systemLow = StarSystem::factory()->create([
            'is_controlled' => true,
            'commander_id' => $commander->id,
        ]);
        $systemLow->stability = 1;
        $systemLow->save();

        // Mock EconomyManager: +10 then -10, returned for each system respectively (single invocation over both systems)
        $mock = \Mockery::mock(EconomyManager::class);
        $mock->shouldReceive('getActiveBonusesForSystem')
            ->andReturn([
                'population_growth_percent' => 0,
                'stability_percent_per_turn' => 10,
            ], [
                'population_growth_percent' => 0,
                'stability_percent_per_turn' => -10,
            ]);
        $this->app->instance(EconomyManager::class, $mock);

        app(PopulationStabilityManager::class)->applyPopulationAndStabilityForTurn(11);

        $systemHigh->refresh();
        $systemLow->refresh();

        $this->assertEquals(100, $systemHigh->stability, 'Upper clamp to 100 failed');
        $this->assertEquals(0, $systemLow->stability, 'Lower clamp to 0 failed');
    }
}
