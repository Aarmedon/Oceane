<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\GameAdminController;
use App\Http\Controllers\Admin\RaceController;
use App\Http\Controllers\Admin\FleetController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Administration MJ (protégé par auth + gm)
Route::middleware(['auth', 'gm'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', [GameAdminController::class, 'index'])->name('dashboard');
        Route::post('/resolve-turn', [GameAdminController::class, 'resolveTurn'])->name('resolve-turn');

        // Page Univers (vue blade existante)
        Route::get('/univers', function () {
            return view('admin.univers.index');
        })->name('univers');

        // CRUD Races
        Route::resource('races', RaceController::class);

        // Fleets (CRUD + annulation suppression planifiée + édition composition)
        Route::resource('fleets', FleetController::class);
        Route::post('fleets/{fleet}/cancel-deletion', [FleetController::class, 'cancelDeletion'])->name('fleets.cancel-deletion');
        Route::post('fleets/{fleet}/composition/add', [FleetController::class, 'addComposition'])->name('fleets.composition.add');
        Route::post('fleets/{fleet}/composition/remove', [FleetController::class, 'removeComposition'])->name('fleets.composition.remove');
    });

require __DIR__.'/auth.php';
