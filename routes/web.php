<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\GameAdminController;
use App\Http\Controllers\Admin\RaceController;
use App\Http\Controllers\Admin\FleetController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;

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

    // Inscriptions / départs
    Route::prefix('enrollment')->as('enrollment.')->group(function () {
        Route::post('/join', [EnrollmentController::class, 'requestJoin'])->name('join');
        Route::post('/leave', [EnrollmentController::class, 'requestLeave'])->name('leave');
    });
    
    // Routes du jeu (protégées par auth)
    Route::prefix('game')->as('game.')->group(function () {
        require __DIR__.'/game.php';
    });
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

        // Inscriptions joueurs (MJ)
        Route::get('enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
        Route::post('enrollments/{enrollment}/block', [AdminEnrollmentController::class, 'block'])->name('enrollments.block');
        Route::post('enrollments/{enrollment}/unblock', [AdminEnrollmentController::class, 'unblock'])->name('enrollments.unblock');
    });

require __DIR__.'/auth.php';
