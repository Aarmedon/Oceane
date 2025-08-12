<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\CommandantManager;

class DashboardController extends Controller
{
    /**
     * Afficher le tableau de bord de l'utilisateur
     */
    public function index()
    {
        $user = Auth::user();
        
        // Vérifier si l'utilisateur a au moins un commandant
        $hasCommander = $user->commanders()->exists();
        
        return view('dashboard', [
            'user' => $user,
            'hasCommander' => $hasCommander,
        ]);
    }
}
