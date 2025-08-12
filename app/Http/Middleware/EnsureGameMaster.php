<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGameMaster
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Autoriser si l'utilisateur a le flag is_admin (attribut Eloquent)
        if ((bool) ($user->is_admin ?? false)) {
            return $next($request);
        }

        $emails = collect(config('oceane.admin.gamemasters_emails', []))
            ->filter()
            ->map(fn ($e) => strtolower(trim($e)))
            ->all();

        if (in_array(strtolower((string) $user->email), $emails, true)) {
            return $next($request);
        }

        abort(403, 'Accès réservé au Maître du Jeu');
    }
}
