<?php

namespace Tests\Feature;

use App\Models\Commander;
use App\Models\GameState;
use App\Models\Good;
use App\Models\Planet;
use App\Models\Population;
use App\Models\StarSystem;
use App\Models\SystemGood;
use App\Services\GameCycleManager;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PopulationStabilityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure base GameState exists for services that expect it
        GameState::create([
            'current_turn' => 1,
            'last_resolved_at' => null,
            'next_turn_at' => now()->addHours(config('oceane.game.hours_between_turns', 24)),
        ]);

        // Seed goods, bonuses, and mapping
        $this->seed(DatabaseSeeder::class);
    }

    public function test_turn_execution_applies_population_and_stability_bonuses_from_goods_stock(): void
    {
        // Arrange system and commander
        $commander = Commander::factory()->create();
        $system = StarSystem::factory()->create([
            'is_controlled' => true,
            'commander_id' => $commander->id,
        ]);
        $system->stability = 50;
        $system->save();

        // Colonized planets with population
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

        // Provide system goods stocks >= 100 for 'food' (+10% pop) and 'holofilm' (+1 stability/turn)
        $food = Good::where('code', 'food')->firstOrFail();
        $holo = Good::where('code', 'holofilm')->firstOrFail();

        SystemGood::updateOrCreate([
            'star_system_id' => $system->id,
            'commander_id' => $commander->id,
            'good_id' => $food->id,
        ], [
            'stock' => 150,
            'production_per_turn' => 0,
        ]);
        SystemGood::updateOrCreate([
            'star_system_id' => $system->id,
            'commander_id' => $commander->id,
            'good_id' => $holo->id,
        ], [
            'stock' => 100,
            'production_per_turn' => 0,
        ]);

        Event::fake([MessageLogged::class]);

        // Act: run a full turn
        app(GameCycleManager::class)->executeTurn();

        // Assert population grew by 10%
        $this->assertDatabaseHas('populations', ['planet_id' => $p1->id, 'population' => 1100]);
        $this->assertDatabaseHas('populations', ['planet_id' => $p2->id, 'population' => 2200]);

        // Assert stability increased by +1 and clamped within bounds
        $system->refresh();
        $this->assertEquals(51, $system->stability);

        // Assert log emitted from PopulationStabilityManager with expected details
        Event::assertDispatched(MessageLogged::class, function ($event) use ($system) {
            return $event->level === 'info'
                && is_string($event->message)
                && str_contains($event->message, 'Goods bonuses in system')
                && str_contains($event->message, (string)($system->name ?? ('#'.$system->id)))
                && str_contains($event->message, 'population_growth_percent=10')
                && str_contains($event->message, 'stability_percent_per_turn=1')
                && str_contains($event->message, 'planets_updated=2')
                && str_contains($event->message, 'total_delta=300');
        });
    }
}
