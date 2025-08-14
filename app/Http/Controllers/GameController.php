<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Commander;
use App\Models\Galaxy;
use App\Models\Race;
use App\Models\Sector;
use App\Models\StarSystem;
use App\Models\Planet;
use App\Models\Fleet;
use App\Models\Order;
use App\Models\TurnReport;
use App\Models\GameState;
use App\Services\CommandantManager;
use App\Services\FleetManager;
use App\Services\TechnologyManager;
use App\Services\VisibilityService;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    protected $commandantManager;
    protected $fleetManager;
    protected $technologyManager;
    
    public function __construct(
        CommandantManager $commandantManager,
        FleetManager $fleetManager,
        TechnologyManager $technologyManager
    ) {
        $this->middleware('auth');
        $this->commandantManager = $commandantManager;
        $this->fleetManager = $fleetManager;
        $this->technologyManager = $technologyManager;
    }
    
    /**
     * Afficher le tableau de bord principal du jeu
     */
    public function dashboard()
    {
        $user = Auth::user();
        $commander = $user->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $turnReports = $commander->turnReports()
                                ->orderByDesc('turn_number')
                                ->limit(5)
                                ->get();
                                
        $currentTurn = (int) (GameState::query()->value('current_turn') ?? 1);
        $unreadReportsCount = $commander->turnReports()
                                       ->where('is_read', false)
                                       ->count();
                                       
        $systemsCount = $commander->starSystems()->count();
        $planetsCount = $commander->planets()->count();
        $fleetsCount = $commander->fleets()->count();
        
        $latestOrders = $commander->orders()
                                 ->where('is_processed', false)
                                 ->orderBy('turn_execution')
                                 ->limit(10)
                                 ->get();
        
        return view('game.dashboard', compact(
            'commander', 
            'turnReports', 
            'currentTurn', 
            'unreadReportsCount',
            'systemsCount',
            'planetsCount',
            'fleetsCount',
            'latestOrders'
        ));
    }
    
    /**
     * Afficher le formulaire de création d'un commandant
     */
    public function createCommanderForm()
    {
        $user = Auth::user();
        
        if ($user->commanders()->exists()) {
            return redirect()->route('game.dashboard');
        }
        
        // Utiliser une liste statique de races au lieu de récupérer depuis la base de données
        $races = collect([
            (object)[
                'id' => 1,
                'name' => 'Humains',
                'description' => 'Race polyvalente avec des bonus en diplomatie et commerce.',
                'bonuses' => json_encode(['diplomatie' => 10, 'commerce' => 15]),
                'penalties' => json_encode(['recherche' => -5]),
            ],
            (object)[
                'id' => 2,
                'name' => 'Zorg',
                'description' => 'Race guerrière avec des bonus en combat et construction de vaisseaux.',
                'bonuses' => json_encode(['combat' => 20, 'construction' => 10]),
                'penalties' => json_encode(['diplomatie' => -15]),
            ],
            (object)[
                'id' => 3,
                'name' => 'Eldari',
                'description' => 'Race ancienne avec des bonus en recherche et technologie.',
                'bonuses' => json_encode(['recherche' => 25, 'technologie' => 15]),
                'penalties' => json_encode(['production' => -10]),
            ],
            (object)[
                'id' => 4,
                'name' => 'Nexus',
                'description' => 'Race cybernétique avec des bonus en production et efficacité.',
                'bonuses' => json_encode(['production' => 20, 'efficacité' => 15]),
                'penalties' => json_encode(['croissance' => -15]),
            ],
        ]);
        
        return view('game.create_commander', compact('races'));
    }
    
    /**
     * Créer un nouveau commandant pour le joueur
     */
    public function storeCommander(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:commanders,name',
            'race_id' => 'required|integer|min:1|max:4', // Vérifier que l'ID de race est entre 1 et 4
            'description' => 'nullable|string|max:1000',
        ]);
        
        $user = Auth::user();
        
        if ($user->commanders()->exists()) {
            return redirect()->route('game.dashboard')
                   ->with('error', 'Vous avez déjà un commandant.');
        }
        
        try {
            $commander = $this->commandantManager->createCommander(
                $user,
                $request->name,
                $request->race_id,
                $request->description
            );
            
            // Attribuer un système de départ au commandant
            $startingSystem = $this->commandantManager->assignStartingSystem($commander);
            
            return redirect()->route('game.dashboard')
                   ->with('success', 'Commandant créé avec succès ! Votre système de départ est ' . $startingSystem->name);
                   
        } catch (\Exception $e) {
            return back()->withInput()
                   ->with('error', 'Erreur lors de la création du commandant: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher la carte galactique
     */
    public function galaxyMap(Request $request)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        // Déterminer quelle galaxie afficher
        $galaxyId = $request->input('galaxy_id', null);
        
        if (!$galaxyId) {
            // Par défaut, afficher la galaxie du système capital du joueur
            $capitalSystem = StarSystem::find($commander->capital_system_id);
            $galaxyId = $capitalSystem ? $capitalSystem->sector->galaxy_id : 1;
        }
        
        $galaxy = Galaxy::findOrFail($galaxyId);
        $galaxies = Galaxy::all();
        
        // Récupérer les secteurs de cette galaxie
        $sectors = Sector::where('galaxy_id', $galaxyId)->get();
        
        // Récupérer les systèmes stellaires de cette galaxie
        $starSystems = StarSystem::whereHas('sector', function($query) use ($galaxyId) {
            $query->where('galaxy_id', $galaxyId);
        })->with('sector', 'commander')->get();
        
        // Récupérer les flottes du joueur dans cette galaxie
        $fleets = Fleet::where('commander_id', $commander->id)
                      ->where('galaxy_id', $galaxyId)
                      ->with('currentSystem')
                      ->get();
        
        return view('game.galaxy_map', compact(
            'commander',
            'galaxy',
            'galaxies',
            'sectors',
            'starSystems',
            'fleets'
        ));
    }

    /**
     * API: Retourner les données minimales de la carte galactique (JSON)
     */
    public function mapApi(Request $request, VisibilityService $visibilityService)
    {
        $user = Auth::user();
        $commander = $user->commanders()->first();

        if (!$commander) {
            return response()->json(['error' => 'Commander not found'], 403);
        }

        // Déterminer la galaxie ciblée
        $galaxyId = (int) $request->input('galaxy_id', 0);
        if (!$galaxyId) {
            $capitalSystem = StarSystem::find($commander->capital_system_id);
            $galaxyId = $capitalSystem ? (int) $capitalSystem->sector->galaxy_id : 1;
        }

        // Déterminer le mode admin (autorisé seulement pour les MJ)
        $gmEmails = (array) config('oceane.admin.gamemasters_emails', []);
        $isGameMaster = in_array($user->email, $gmEmails, true);
        $adminRequested = (bool) $request->boolean('admin', false);
        $admin = $isGameMaster && $adminRequested;

        $data = $visibilityService->buildMapData($commander, $galaxyId, $admin);
        // Add commander info for consistent centering on frontend
        $capSystem = StarSystem::find($commander->capital_system_id);
        $data['commander'] = [
            'id' => (int) $commander->id,
            'capital_system_id' => (int) $commander->capital_system_id,
            'capital_x' => $capSystem ? (int) $capSystem->position_x : null,
            'capital_y' => $capSystem ? (int) $capSystem->position_y : null,
        ];

        return response()->json($data);
    }
    
    /**
     * Afficher les détails d'un système stellaire
     */
    public function showStarSystem($id)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $starSystem = StarSystem::with(['sector.galaxy', 'planets', 'commander'])->findOrFail($id);
        
        // Récupérer les flottes dans ce système
        $fleets = Fleet::where('current_system_id', $starSystem->id)
                      ->with('ships', 'commander')
                      ->get();
                      
        // Déterminer si le système est visible pour le joueur
        $isVisible = $starSystem->commander_id == $commander->id || 
                     $fleets->where('commander_id', $commander->id)->count() > 0;
                     
        if (!$isVisible) {
            // Si le système n'est pas directement visible, vérifier s'il est dans la portée des scanners du joueur
            $isInScanRange = $this->isSystemInScanRange($starSystem, $commander);
            
            if (!$isInScanRange) {
                return redirect()->route('game.galaxy_map')
                       ->with('error', 'Ce système est en dehors de votre portée de scan.');
            }
        }
        
        return view('game.star_system', compact(
            'commander',
            'starSystem',
            'fleets',
            'isVisible'
        ));
    }
    
    /**
     * Vérifier si un système est dans la portée des scanners du joueur
     */
    private function isSystemInScanRange(StarSystem $system, Commander $commander)
    {
        // Récupérer tous les systèmes contrôlés par le joueur
        $ownedSystems = $commander->starSystems;
        
        // Récupérer la position de toutes les flottes du joueur
        $fleetSystems = $commander->fleets()
                                 ->with('currentSystem')
                                 ->get()
                                 ->pluck('currentSystem');
                                 
        // Combiner les systèmes pour les points de référence de scan
        $scanPoints = $ownedSystems->merge($fleetSystems);
        
        // Portée de scan de base
        $baseScanRange = config('oceane.commander.base_scan_range', 10);
        
        // Vérifier si une technologie améliore la portée de scan
        $scanTech = $commander->technologies()
                             ->where('category', 'sensors')
                             ->first();
                             
        $scanBonus = $scanTech ? $scanTech->pivot->level : 0;
        $totalScanRange = $baseScanRange + $scanBonus;
        
        // Vérifier pour chaque point de scan si le système cible est à portée
        foreach ($scanPoints as $scanPoint) {
            if (!$scanPoint) continue;
            
            // Si les systèmes sont dans des galaxies différentes, ils ne sont pas à portée de scan
            if ($scanPoint->sector->galaxy_id != $system->sector->galaxy_id) {
                continue;
            }
            
            // Calculer la distance entre les systèmes
            $distance = sqrt(
                pow($system->position_x - $scanPoint->position_x, 2) + 
                pow($system->position_y - $scanPoint->position_y, 2)
            );
            
            if ($distance <= $totalScanRange) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Afficher les détails d'une planète
     */
    public function showPlanet($id)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $planet = Planet::with(['starSystem.sector.galaxy', 'commander', 'buildings'])->findOrFail($id);
        
        // Vérifier si la planète est visible pour le joueur
        $isOwner = $planet->commander_id == $commander->id;
        $isSystemOwner = $planet->starSystem->commander_id == $commander->id;
        
        // Si le joueur n'est ni propriétaire de la planète ni du système, vérifier la présence de flottes
        $hasFleetInSystem = false;
        if (!$isOwner && !$isSystemOwner) {
            $hasFleetInSystem = Fleet::where('current_system_id', $planet->star_system_id)
                                    ->where('commander_id', $commander->id)
                                    ->exists();
        }
        
        if (!$isOwner && !$isSystemOwner && !$hasFleetInSystem) {
            return redirect()->route('game.star_system', $planet->star_system_id)
                   ->with('error', 'Vous n\'avez pas accès aux détails de cette planète.');
        }
        
        return view('game.planet', compact(
            'commander',
            'planet',
            'isOwner'
        ));
    }
    
    /**
     * Afficher les flottes du joueur
     */
    public function fleets()
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $fleets = $commander->fleets()
                           ->with(['currentSystem', 'destinationSystem', 'ships'])
                           ->get();
                           
        return view('game.fleets.index', compact('commander', 'fleets'));
    }
    
    /**
     * Afficher les détails d'une flotte
     */
    public function showFleet($id)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $fleet = Fleet::where('commander_id', $commander->id)
                     ->with(['ships', 'currentSystem', 'destinationSystem', 'hero'])
                     ->findOrFail($id);
        
        return view('game.fleets.show', compact('commander', 'fleet'));
    }
    
    /**
     * Afficher le formulaire de création d'une flotte
     */
    public function createFleetForm($systemId = null)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        // Récupérer le système où créer la flotte
        if ($systemId) {
            $system = StarSystem::findOrFail($systemId);
            
            // Vérifier que le commandant possède le système ou a une flotte à cet endroit
            $hasAccess = $system->commander_id == $commander->id || 
                        Fleet::where('commander_id', $commander->id)
                             ->where('current_system_id', $systemId)
                             ->exists();
                             
            if (!$hasAccess) {
                return redirect()->route('game.star_system', $systemId)
                       ->with('error', 'Vous ne pouvez pas créer de flotte dans ce système.');
            }
        } else {
            // Liste des systèmes où le joueur peut créer une flotte
            $availableSystems = StarSystem::where('commander_id', $commander->id)
                                         ->orWhereHas('fleets', function($query) use ($commander) {
                                             $query->where('commander_id', $commander->id);
                                         })
                                         ->get();
                                         
            if ($availableSystems->isEmpty()) {
                return redirect()->route('game.dashboard')
                       ->with('error', 'Vous n\'avez aucun système où créer une flotte.');
            }
            
            $system = null;
            return view('game.fleets.create', compact('commander', 'system', 'availableSystems'));
        }
        
        return view('game.fleets.create', compact('commander', 'system'));
    }
    
    /**
     * Créer une nouvelle flotte
     */
    public function storeFleet(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'system_id' => 'required|exists:star_systems,id',
        ]);
        
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $system = StarSystem::findOrFail($request->system_id);
        
        try {
            $fleet = $this->fleetManager->createFleet($commander, $system, $request->name);
            
            return redirect()->route('game.fleets.show', $fleet->id)
                   ->with('success', 'Flotte créée avec succès !');
                   
        } catch (\Exception $e) {
            return back()->withInput()
                   ->with('error', 'Erreur lors de la création de la flotte: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher la page des technologies
     */
    public function technologies()
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        // Récupérer les technologies actuelles du commandant
        $commanderTechnologies = $commander->technologies;
        
        // Récupérer les technologies disponibles pour la recherche
        $availableTechnologies = $this->technologyManager->getAvailableTechnologies($commander);
        
        return view('game.technologies.index', compact('commander', 'commanderTechnologies', 'availableTechnologies'));
    }
    
    /**
     * Démarrer une recherche technologique
     */
    public function startResearch(Request $request)
    {
        $request->validate([
            'technology_id' => 'required|exists:technologies,id',
        ]);
        
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $technology = \App\Models\Technology::findOrFail($request->technology_id);
        
        try {
            $result = $this->technologyManager->startResearch($commander, $technology);
            
            return redirect()->route('game.technologies')
                   ->with('success', 'Recherche de ' . $technology->name . ' niveau ' . $result['target_level'] . ' démarrée ! Complétion prévue au tour ' . $result['completion_turn']);
                   
        } catch (\Exception $e) {
            return back()->withInput()
                   ->with('error', 'Erreur lors du démarrage de la recherche: ' . $e->getMessage());
        }
    }
    
    /**
     * Afficher la liste des ordres du joueur
     */
    public function orders()
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $pendingOrders = $commander->orders()
                                  ->where('is_processed', false)
                                  ->orderBy('turn_execution')
                                  ->get();
                                  
        $processedOrders = $commander->orders()
                                    ->where('is_processed', true)
                                    ->orderByDesc('updated_at')
                                    ->limit(20)
                                    ->get();
                                    
        $currentTurn = (int) (GameState::query()->value('current_turn') ?? 1);
        
        return view('game.orders.index', compact('commander', 'pendingOrders', 'processedOrders', 'currentTurn'));
    }
    
    /**
     * Annuler un ordre
     */
    public function cancelOrder($id)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $order = Order::where('commander_id', $commander->id)
                     ->where('is_processed', false)
                     ->findOrFail($id);
                     
        $order->delete();
        
        return redirect()->route('game.orders')
               ->with('success', 'Ordre annulé avec succès.');
    }
    
    /**
     * Afficher les rapports de tour du joueur
     */
    public function reports()
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $reports = $commander->turnReports()
                            ->orderByDesc('turn_number')
                            ->paginate(10);
                            
        return view('game.reports.index', compact('commander', 'reports'));
    }
    
    /**
     * Afficher un rapport de tour spécifique
     */
    public function showReport($id)
    {
        $commander = Auth::user()->commanders()->first();
        
        if (!$commander) {
            return redirect()->route('game.create_commander');
        }
        
        $report = TurnReport::where('commander_id', $commander->id)
                           ->findOrFail($id);
                           
        // Marquer le rapport comme lu
        if (!$report->is_read) {
            $report->update(['is_read' => true]);
        }
        
        return view('game.reports.show', compact('commander', 'report'));
    }
}
