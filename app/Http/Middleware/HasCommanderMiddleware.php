<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HasCommanderMiddleware
{
    /**
     * Vérifie si l'utilisateur a un commandant.
     * Si l'utilisateur n'a pas de commandant, il est redirigé vers la page de création de commandant.
     * Si l'utilisateur a un commandant, la requête continue normalement.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Si l'utilisateur n'a pas de commandant et n'est pas sur la page de création de commandant
        if (!$user->commanders()->exists() && !$request->routeIs('game.create_commander') && !$request->routeIs('game.store_commander')) {
            return redirect()->route('game.create_commander');
        }
        
        // Si l'utilisateur a déjà un commandant et est sur la page de création de commandant
        if ($user->commanders()->exists() && ($request->routeIs('game.create_commander') || $request->routeIs('game.store_commander'))) {
            return redirect()->route('game.dashboard');
        }
        
        return $next($request);
    }
}
