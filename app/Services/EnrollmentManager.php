<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\Fleet;
use App\Models\GameEnrollment;
use App\Models\GameEvent;
use App\Models\GameState;
use App\Models\Hero;
use App\Models\Planet;
use App\Models\StarSystem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnrollmentManager
{
    public function __construct(
        protected CommandantManager $commandantManager,
    ) {}

    /**
     * Player requests to join the game; stored as pending.
     * Enforced: registration open and freeze window not active.
     */
    public function requestJoin(User $user, array $payload = []): GameEnrollment
    {
        if (!config('oceane.game.registration_open', true)) {
            throw new \Exception('Les inscriptions sont fermées.');
        }

        if ($this->isFreezeWindowActive()) {
            throw new \Exception('La fenêtre de gel est active. Les demandes d\'inscription sont closes avant la résolution du tour.');
        }

        if ($user->commanders()->exists()) {
            throw new \Exception('Vous avez déjà un commandant.');
        }

        $existing = GameEnrollment::where('user_id', $user->id)
            ->where('type', 'join')
            ->where('status', 'pending')
            ->exists();
        if ($existing) {
            throw new \Exception('Une demande d\'inscription est déjà en attente.');
        }

        return GameEnrollment::create([
            'user_id' => $user->id,
            'type' => 'join',
            'status' => 'pending',
            'payload' => $payload,
        ]);
    }

    /**
     * Player requests to leave the game; stored as pending.
     * Enforced: freeze window not active.
     */
    public function requestLeave(User $user, array $payload = []): GameEnrollment
    {
        if ($this->isFreezeWindowActive()) {
            throw new \Exception('La fenêtre de gel est active. Les demandes de départ sont closes avant la résolution du tour.');
        }

        $commander = $user->commanders()->first();
        if (!$commander) {
            throw new \Exception('Aucun commandant associé à cet utilisateur.');
        }

        $existing = GameEnrollment::where('user_id', $user->id)
            ->where('type', 'leave')
            ->where('status', 'pending')
            ->exists();
        if ($existing) {
            throw new \Exception('Une demande de départ est déjà en attente.');
        }

        return GameEnrollment::create([
            'user_id' => $user->id,
            'commander_id' => $commander->id,
            'type' => 'leave',
            'status' => 'pending',
            'payload' => $payload,
        ]);
    }

    /**
     * GM blocks a request prior to turn processing.
     */
    public function blockRequest(GameEnrollment $request, User $gm, ?string $note = null): void
    {
        if (!config('oceane.enrollment.allow_gm_block', true)) {
            throw new \Exception('Le blocage par MJ est désactivé.');
        }

        if ($request->status !== 'pending') {
            throw new \Exception('Seules les demandes en attente peuvent être bloquées.');
        }

        $request->update([
            'status' => 'blocked',
            'processed_by' => $gm->id,
            'note' => $note,
        ]);
    }

    /**
     * Process all pending enrollment requests at turn resolution.
     * Should be called inside the game turn transaction.
     */
    public function processPendingAtTurn(int $turn): void
    {
        $autoApprove = (bool) config('oceane.enrollment.auto_approve', true);

        // Process in FIFO order for determinism
        $requests = GameEnrollment::where('status', 'pending')
            ->orderBy('id')
            ->get();

        foreach ($requests as $req) {
            try {
                if (!$autoApprove) {
                    // If not auto-approving, skip processing; keep pending
                    continue;
                }

                if ($req->type === 'join') {
                    $this->approveJoin($req, $turn);
                } elseif ($req->type === 'leave') {
                    $this->approveLeave($req, $turn);
                }
            } catch (\Throwable $e) {
                Log::error('Erreur traitement inscription', [
                    'request_id' => $req->id,
                    'type' => $req->type,
                    'error' => $e->getMessage(),
                ]);

                $req->update([
                    'status' => 'rejected',
                    'processed_at' => now(),
                    'processed_turn' => $turn,
                    'note' => ($req->note ? ($req->note.' | ') : '') . 'Erreur: '.$e->getMessage(),
                ]);
            }
        }
    }

    protected function approveJoin(GameEnrollment $req, int $turn): void
    {
        $user = User::findOrFail($req->user_id);
        if ($user->commanders()->exists()) {
            // Already joined by out-of-band action; mark processed
            $req->update([
                'status' => 'processed',
                'processed_at' => now(),
                'processed_turn' => $turn,
                'note' => 'Déjà en jeu au moment du traitement.'
            ]);
            return;
        }

        $name = $req->payload['commander_name'] ?? ('Cmdr '.$user->name);
        $raceId = (int) ($req->payload['race_id'] ?? 1);
        $description = $req->payload['description'] ?? null;

        // Create commander and assign starting system
        $commander = $this->commandantManager->createCommander($user, $name, $raceId, $description);
        $this->commandantManager->assignStartingSystem($commander);

        // Mark processed
        $req->update([
            'status' => 'processed',
            'commander_id' => $commander->id,
            'processed_at' => now(),
            'processed_turn' => $turn,
        ]);

        // Log event
        GameEvent::create([
            'turn_number' => $turn,
            'event_type' => 'player_joined',
            'event_data' => [
                'user_id' => $user->id,
                'commander_id' => $commander->id,
                'commander_name' => $commander->name,
            ],
            'involved_commanders' => [$commander->id],
            'is_public' => true,
        ]);
    }

    protected function approveLeave(GameEnrollment $req, int $turn): void
    {
        $policy = (string) config('oceane.enrollment.default_leave_policy', 'neutral_takeover');
        $commander = Commander::find($req->commander_id);
        if (!$commander) {
            // nothing to transfer
            $req->update([
                'status' => 'processed',
                'processed_at' => now(),
                'processed_turn' => $turn,
                'note' => 'Aucun commandant à transférer',
            ]);
            return;
        }

        switch ($policy) {
            case 'neutral_takeover':
            default:
                $neutral = $this->ensureNeutralCommander();
                $this->transferAssetsTo($commander, $neutral);
                $note = 'Transfert au commandant neutre';
                $involved = [$commander->id, $neutral->id];
                break;
        }

        // Mark processed
        $req->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processed_turn' => $turn,
            'note' => $note ?? null,
        ]);

        GameEvent::create([
            'turn_number' => $turn,
            'event_type' => 'player_left',
            'event_data' => [
                'commander_id' => $commander->id,
                'commander_name' => $commander->name,
            ],
            'involved_commanders' => $involved ?? [$commander->id],
            'is_public' => true,
        ]);
    }

    protected function transferAssetsTo(Commander $from, Commander $to): void
    {
        // Systems
        StarSystem::where('commander_id', $from->id)->update(['commander_id' => $to->id]);
        // Planets
        Planet::where('commander_id', $from->id)->update(['commander_id' => $to->id]);
        // Fleets
        Fleet::where('commander_id', $from->id)->update(['commander_id' => $to->id]);
        // Heroes
        Hero::where('commander_id', $from->id)->update(['commander_id' => $to->id]);
    }

    protected function ensureNeutralCommander(): Commander
    {
        $email = config('oceane.enrollment.neutral.user_email', 'neutral@oceane.local');
        $userName = config('oceane.enrollment.neutral.user_name', 'Neutral');
        $commanderName = config('oceane.enrollment.neutral.commander_name', 'Neutral');
        $raceId = (int) config('oceane.enrollment.neutral.race_id', 1);

        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $userName,
                'email' => $email,
                'password' => Str::random(32),
            ]);
        }

        $commander = $user->commanders()->where('name', $commanderName)->first();
        if (!$commander) {
            $commander = Commander::where('name', $commanderName)->first();
        }
        if (!$commander) {
            $commander = $this->commandantManager->createCommander($user, $commanderName, $raceId, 'Commandant neutre');
        }

        return $commander;
    }

    /**
     * Returns true if we are within the freeze window before next turn.
     */
    public function isFreezeWindowActive(): bool
    {
        $minutes = (int) config('oceane.enrollment.freeze_window_minutes', 60);
        $state = GameState::query()->first();
        if (!$state || !$state->next_turn_at) {
            return false;
        }
        if ($state->next_turn_at->isPast()) {
            return false;
        }
        return now()->diffInMinutes($state->next_turn_at) <= $minutes;
    }
}
