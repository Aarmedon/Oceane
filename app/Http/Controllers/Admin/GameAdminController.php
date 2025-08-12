<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ResolveTurnJob;
use App\Models\GameState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameAdminController extends Controller
{
    /**
     * Dashboard Admin (MJ)
     */
    public function index(Request $request): View
    {
        $currentTurn = (int) (GameState::query()->value('current_turn') ?? 1);
        $hoursBetweenTurns = (int) config('oceane.game.hours_between_turns', 24);

        return view('admin.dashboard', [
            'currentTurn' => $currentTurn,
            'hoursBetweenTurns' => $hoursBetweenTurns,
        ]);
    }

    /**
     * Déclencher la résolution d'un tour
     */
    public function resolveTurn(Request $request): RedirectResponse
    {
        $request->validate([
            // rien pour le moment; garde pour l'extension future
        ]);

        try {
            // Exécution synchrone pour éviter la dépendance au driver de queue pendant la mise en place
            ResolveTurnJob::dispatchSync(optional($request->user())->id);

            return back()->with('success', 'Résolution du tour exécutée avec succès.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Erreur pendant la résolution du tour: ' . $e->getMessage());
        }
    }
}
