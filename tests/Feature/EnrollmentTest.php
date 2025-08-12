<?php

namespace Tests\Feature;

use App\Jobs\ResolveTurnJob;
use App\Models\Commander;
use App\Models\GameEnrollment;
use App\Models\GameState;
use App\Models\StarSystem;
use App\Models\Race;
use App\Models\User;
use App\Services\GameCycleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure base GameState exists for tests that rely on it
        GameState::create([
            'current_turn' => 1,
            'last_resolved_at' => null,
            'next_turn_at' => now()->addHours(config('oceane.game.hours_between_turns', 24)),
        ]);
    }

    public function test_join_request_fails_during_freeze_window(): void
    {
        config()->set('oceane.enrollment.freeze_window_minutes', 60);

        // Set next turn within 30 minutes (inside freeze window)
        GameState::query()->update(['next_turn_at' => now()->addMinutes(30)]);

        $user = User::factory()->create();
        $race = Race::factory()->create();

        $resp = $this->actingAs($user)->post(route('enrollment.join'), [
            'commander_name' => 'Test Cmdr',
            'race_id' => $race->id,
        ]);

        $resp->assertSessionHasErrors('enrollment');
        $this->assertDatabaseCount('game_enrollments', 0);
    }

    public function test_leave_request_fails_during_freeze_window(): void
    {
        config()->set('oceane.enrollment.freeze_window_minutes', 60);
        GameState::query()->update(['next_turn_at' => now()->addMinutes(30)]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $race->id,
        ]);

        $resp = $this->actingAs($user)->post(route('enrollment.leave'), [
            'note' => 'bye',
        ]);

        $resp->assertSessionHasErrors('enrollment');
        $this->assertDatabaseCount('game_enrollments', 0);
    }

    public function test_duplicate_join_request_prevented(): void
    {
        config()->set('oceane.enrollment.freeze_window_minutes', 0);
        $user = User::factory()->create();
        $race = Race::factory()->create();

        $first = $this->actingAs($user)->post(route('enrollment.join'), [
            'commander_name' => 'Cmdr One',
            'race_id' => $race->id,
        ]);
        $first->assertSessionHasNoErrors();
        $this->assertDatabaseHas('game_enrollments', [
            'user_id' => $user->id,
            'type' => 'join',
            'status' => 'pending',
        ]);

        $second = $this->actingAs($user)->post(route('enrollment.join'), [
            'commander_name' => 'Cmdr One Again',
            'race_id' => $race->id,
        ]);
        $second->assertSessionHasErrors('enrollment');
        $this->assertDatabaseCount('game_enrollments', 1);
    }

    public function test_process_pending_join_creates_commander_and_marks_processed(): void
    {
        // Ensure auto-approve is on
        config()->set('oceane.enrollment.auto_approve', true);

        // Create a free star system to be assigned
        StarSystem::factory()->create(['is_controlled' => false]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        // Create a pending join request
        $enr = GameEnrollment::create([
            'user_id' => $user->id,
            'type' => 'join',
            'status' => 'pending',
            'payload' => ['commander_name' => 'JoinCmdr', 'description' => 'desc', 'race_id' => $race->id],
        ]);

        // Execute a turn (sync)
        app(GameCycleManager::class)->executeTurn();

        $this->assertDatabaseHas('commanders', [
            'user_id' => $user->id,
            'name' => 'JoinCmdr',
        ]);

        $enr->refresh();
        $this->assertEquals('processed', $enr->status);
        $this->assertNotNull($enr->processed_at);
        $this->assertNotNull($enr->processed_turn);
    }

    public function test_process_pending_leave_transfers_assets_to_neutral(): void
    {
        // Ensure auto-approve is on
        config()->set('oceane.enrollment.auto_approve', true);
        config()->set('oceane.enrollment.default_leave_policy', 'neutral_takeover');

        // Prepare a commander with a controlled system
        $user = User::factory()->create();
        $playerRace = Race::factory()->create();
        $commander = Commander::factory()->create([
            'user_id' => $user->id,
            'race_id' => $playerRace->id,
        ]);
        $neutralRace = Race::factory()->create();
        config()->set('oceane.enrollment.neutral.race_id', $neutralRace->id);
        $sys = StarSystem::factory()->create([
            'is_controlled' => true,
            'commander_id' => $commander->id,
        ]);

        // Create leave request
        $enr = GameEnrollment::create([
            'user_id' => $user->id,
            'commander_id' => $commander->id,
            'type' => 'leave',
            'status' => 'pending',
            'payload' => ['note' => 'bye'],
        ]);

        // Execute a turn
        app(GameCycleManager::class)->executeTurn();

        // Neutral should exist and own the system now
        $neutralEmail = config('oceane.enrollment.neutral.user_email', 'neutral@oceane.local');
        $neutralUser = User::where('email', $neutralEmail)->first();
        $this->assertNotNull($neutralUser);
        $neutralCommander = $neutralUser->commanders()->first();
        $this->assertNotNull($neutralCommander);

        $sys->refresh();
        $this->assertEquals($neutralCommander->id, $sys->commander_id);

        $enr->refresh();
        $this->assertEquals('processed', $enr->status);
        $this->assertStringContainsString('Transfert au commandant neutre', (string) $enr->note);
    }

    public function test_gm_can_block_and_unblock_enrollment(): void
    {
        // Configure GM access by email
        config()->set('oceane.admin.gamemasters_emails', ['gm@example.com']);

        $gm = User::factory()->create(['email' => 'gm@example.com']);
        $user = User::factory()->create();
        $enr = GameEnrollment::create([
            'user_id' => $user->id,
            'type' => 'join',
            'status' => 'pending',
        ]);

        // Block
        $respBlock = $this->actingAs($gm)->post(route('admin.enrollments.block', $enr), [
            'note' => 'manual review',
        ]);
        $respBlock->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseHas('game_enrollments', [
            'id' => $enr->id,
            'status' => 'blocked',
        ]);

        // Unblock
        $respUnblock = $this->actingAs($gm)->post(route('admin.enrollments.unblock', $enr), [
            'note' => 'ok',
        ]);
        $respUnblock->assertRedirect(route('admin.enrollments.index'));
        $this->assertDatabaseHas('game_enrollments', [
            'id' => $enr->id,
            'status' => 'pending',
        ]);
    }
}
