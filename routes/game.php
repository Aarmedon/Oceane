<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Game\OrderController;
use App\Http\Controllers\Game\ReportController;
use App\Http\Controllers\GameController;

/*
|--------------------------------------------------------------------------
| Routes du jeu
|--------------------------------------------------------------------------
|
| Routes pour les fonctionnalités du jeu Océane 2
|
*/

// Tableau de bord du jeu
Route::get('/', [GameController::class, 'dashboard'])->name('dashboard');

// Création d'un commandant
Route::get('/create-commander', [GameController::class, 'createCommanderForm'])->name('create_commander');
Route::post('/create-commander', [GameController::class, 'storeCommander'])->name('store_commander');

// Carte galactique
Route::get('/galaxy-map', [GameController::class, 'galaxyMap'])->name('galaxy_map');

// Système stellaire
Route::get('/star-system/{id}', [GameController::class, 'starSystem'])->name('star_system');

// Technologies
Route::get('/technologies', [GameController::class, 'technologies'])->name('technologies');

// Flottes
Route::get('/fleets', [GameController::class, 'fleets'])->name('fleets');

// Alliances
Route::get('/alliances', [GameController::class, 'alliances'])->name('alliances');
Route::get('/alliance/{id}', [GameController::class, 'alliance'])->name('alliance');

// Routes pour les ordres
Route::prefix('orders')->name('orders.')->group(function () {
    // Liste des ordres
    Route::get('/', [OrderController::class, 'index'])->name('index');
    
    // Détails d'un ordre
    Route::get('/{id}/details', [OrderController::class, 'show'])->name('show');
    
    // Création d'un nouvel ordre
    Route::get('/create/{type}', [OrderController::class, 'create'])->name('create');
    Route::post('/create/{type}', [OrderController::class, 'store'])->name('store');
    
    // Annulation d'un ordre
    Route::delete('/{id}/cancel', [OrderController::class, 'cancel'])->name('cancel');
});

// Routes pour les rapports
Route::prefix('reports')->name('reports.')->group(function () {
    // Hub central des rapports
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::post('/refresh-data', [ReportController::class, 'refreshData'])->name('refresh-data');
    
    // Rapports de tour
    Route::prefix('turns')->name('turns.')->group(function () {
        Route::get('/', [ReportController::class, 'listTurnReports'])->name('index');
        Route::get('/{id}', [ReportController::class, 'showTurnReport'])->name('show');
        Route::post('/{id}/read', [ReportController::class, 'markTurnReportAsRead'])->name('read');
        Route::post('/mark-all-read', [ReportController::class, 'markAllTurnReportsAsRead'])->name('mark-all-read');
        Route::get('/{id}/download', [ReportController::class, 'downloadTurnReport'])->name('download');
    });
    
    // Rapports de combat
    Route::prefix('combats')->name('combats.')->group(function () {
        Route::get('/', [ReportController::class, 'listCombatReports'])->name('index');
        Route::get('/{id}', [ReportController::class, 'showCombatReport'])->name('show');
        Route::post('/{id}/read', [ReportController::class, 'markCombatReportAsRead'])->name('read');
        Route::post('/mark-all-read', [ReportController::class, 'markAllCombatReportsAsRead'])->name('mark-all-read');
        Route::get('/{id}/download', [ReportController::class, 'downloadCombatReport'])->name('download');
    });
    
    // Événements de jeu
    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [ReportController::class, 'listGameEvents'])->name('index');
        Route::get('/{id}', [ReportController::class, 'showGameEvent'])->name('show');
        Route::post('/{id}/read', [ReportController::class, 'markGameEventAsRead'])->name('read');
        Route::post('/mark-all-read', [ReportController::class, 'markAllGameEventsAsRead'])->name('mark-all-read');
    });
});
