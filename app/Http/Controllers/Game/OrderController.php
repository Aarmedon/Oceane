<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Commander;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Affiche la liste des ordres pour le commandant connecté
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $commander = Auth::user()->commander;
        
        // Récupérer les ordres du commandant par statut
        $pendingOrders = $commander->orders()
            ->where('is_processed', false)
            ->whereNull('processing_error')
            ->orderBy('turn_execution', 'asc')
            ->get();
            
        $processedOrders = $commander->orders()
            ->where('is_processed', true)
            ->whereNull('processing_error')
            ->orderBy('turn_processed', 'desc')
            ->take(20)
            ->get();
            
        $failedOrders = $commander->orders()
            ->whereNotNull('processing_error')
            ->orderBy('turn_processed', 'desc')
            ->take(10)
            ->get();
        
        // Récupérer les rapports récents pour l'affichage dans l'onglet rapports
        $turnReports = $commander->turnReports()
            ->orderBy('turn_number', 'desc')
            ->take(10)
            ->get();
            
        $combatReports = $commander->combatReports()
            ->orderBy('turn_number', 'desc')
            ->take(10)
            ->get();
            
        $gameEvents = $commander->gameEvents()
            ->orderBy('turn_number', 'desc')
            ->take(15)
            ->get();
        
        // Compter les éléments non lus
        $unreadTurnReports = $turnReports->where('is_read', false)->count();
        $unreadCombatReports = $combatReports->where('is_read', false)->count();
        $unreadGameEvents = $gameEvents->where('is_read', false)->count();
        
        // Récupérer le tour actuel du jeu
        $currentTurn = config('game.current_turn', 1);
        
        // Préparer les données pour la vue
        $ordersData = [
            'orders' => [
                'pending' => $pendingOrders,
                'processed' => $processedOrders,
                'failed' => $failedOrders
            ],
            'current_turn' => $currentTurn
        ];
        
        return view('game.orders.index', compact(
            'pendingOrders', 
            'processedOrders', 
            'failedOrders',
            'turnReports',
            'combatReports',
            'gameEvents',
            'unreadTurnReports',
            'unreadCombatReports',
            'unreadGameEvents',
            'currentTurn',
            'ordersData'
        ));
    }
    
    /**
     * Affiche les détails d'un ordre spécifique
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $commander = Auth::user()->commander;
        $order = Order::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        // Enrichir les données de l'ordre selon son type
        $orderData = $this->enrichOrderData($order);
        
        return response()->json($orderData);
    }
    
    /**
     * Affiche le formulaire de création d'un nouvel ordre selon le type
     *
     * @param string $type
     * @return \Illuminate\View\View
     */
    public function create($type)
    {
        $commander = Auth::user()->commander;
        $currentTurn = config('game.current_turn', 1);
        
        // Préparer les données spécifiques au type d'ordre
        switch ($type) {
            case 'move':
                $fleets = $commander->fleets()->where('is_moving', false)->get();
                $systems = $commander->knownSystems()->get();
                return view('game.orders.create.move', compact('fleets', 'systems', 'currentTurn'));
                
            case 'colonize':
                $colonizationShips = $commander->availableColonizationShips();
                $colonizablePlanets = $commander->colonizablePlanets();
                return view('game.orders.create.colonize', compact('colonizationShips', 'colonizablePlanets', 'currentTurn'));
                
            case 'research':
                $availableTechnologies = $commander->availableTechnologies();
                return view('game.orders.create.research', compact('availableTechnologies', 'currentTurn'));
                
            case 'build':
                $planets = $commander->planets;
                $buildableItems = $commander->buildableItems();
                return view('game.orders.create.build', compact('planets', 'buildableItems', 'currentTurn'));
                
            case 'diplomatic':
                $otherCommanders = Commander::where('id', '!=', $commander->id)->get();
                return view('game.orders.create.diplomatic', compact('otherCommanders', 'currentTurn'));
                
            default:
                return redirect()->route('game.orders.index')
                    ->with('error', 'Type d\'ordre non valide.');
        }
    }
    
    /**
     * Enregistre un nouvel ordre
     *
     * @param Request $request
     * @param string $type
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, $type)
    {
        $commander = Auth::user()->commander;
        $currentTurn = config('game.current_turn', 1);
        
        // Valider les données selon le type d'ordre
        switch ($type) {
            case 'move':
                $validated = $request->validate([
                    'fleet_id' => 'required|exists:fleets,id',
                    'destination_system_id' => 'required|exists:star_systems,id',
                ]);
                
                // Vérifier que la flotte appartient au commandant
                $fleet = $commander->fleets()->findOrFail($validated['fleet_id']);
                
                // Créer les paramètres de l'ordre
                $parameters = [
                    'fleet_id' => $fleet->id,
                    'fleet_name' => $fleet->name,
                    'origin_system_id' => $fleet->star_system_id,
                    'origin_system_name' => $fleet->starSystem->name,
                    'destination_system_id' => $validated['destination_system_id'],
                    'destination_system_name' => $fleet->starSystem->find($validated['destination_system_id'])->name,
                    // Autres paramètres calculés comme la distance, la consommation de carburant, etc.
                ];
                break;
                
            case 'colonize':
                $validated = $request->validate([
                    'ship_id' => 'required|exists:ships,id',
                    'planet_id' => 'required|exists:planets,id',
                ]);
                
                // Vérifier que le vaisseau appartient au commandant
                $ship = $commander->ships()->findOrFail($validated['ship_id']);
                
                // Créer les paramètres de l'ordre
                $parameters = [
                    'ship_id' => $ship->id,
                    'ship_name' => $ship->name,
                    'planet_id' => $validated['planet_id'],
                    // Autres paramètres comme les détails de la planète
                ];
                break;
                
            case 'research':
                $validated = $request->validate([
                    'technology_id' => 'required|exists:technologies,id',
                    'target_level' => 'required|integer|min:1',
                ]);
                
                // Créer les paramètres de l'ordre
                $parameters = [
                    'technology_id' => $validated['technology_id'],
                    'target_level' => $validated['target_level'],
                    // Autres paramètres comme le coût, le temps, etc.
                ];
                break;
                
            case 'build':
                $validated = $request->validate([
                    'build_type' => 'required|in:building,ship',
                    'item_id' => 'required',
                    'location_id' => 'required',
                    'quantity' => 'required|integer|min:1',
                ]);
                
                // Créer les paramètres de l'ordre
                $parameters = [
                    'build_type' => $validated['build_type'],
                    'item_id' => $validated['item_id'],
                    'location_id' => $validated['location_id'],
                    'quantity' => $validated['quantity'],
                    // Autres paramètres comme le coût, le temps, etc.
                ];
                break;
                
            case 'diplomatic':
                $validated = $request->validate([
                    'target_commander_id' => 'required|exists:commanders,id',
                    'diplomatic_type' => 'required|in:alliance_proposal,peace_proposal,trade_proposal,war_declaration',
                    'message' => 'nullable|string|max:500',
                    'duration' => 'required|integer|min:1',
                ]);
                
                // Créer les paramètres de l'ordre
                $parameters = [
                    'target_commander_id' => $validated['target_commander_id'],
                    'diplomatic_type' => $validated['diplomatic_type'],
                    'message' => $validated['message'] ?? '',
                    'duration' => $validated['duration'],
                    // Autres paramètres selon le type diplomatique
                ];
                break;
                
            default:
                return redirect()->route('game.orders.index')
                    ->with('error', 'Type d\'ordre non valide.');
        }
        
        // Calculer le tour d'exécution (par défaut le tour suivant)
        $turnExecution = $currentTurn + 1;
        
        // Créer l'ordre
        $order = new Order([
            'commander_id' => $commander->id,
            'order_type' => $type,
            'parameters' => $parameters,
            'turn_submitted' => $currentTurn,
            'turn_execution' => $turnExecution,
            'is_processed' => false,
            'processing_error' => null,
        ]);
        
        $order->save();
        
        return redirect()->route('game.orders.index')
            ->with('success', 'Ordre créé avec succès.');
    }
    
    /**
     * Annule un ordre en attente
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel($id)
    {
        $commander = Auth::user()->commander;
        $order = Order::where('id', $id)
            ->where('commander_id', $commander->id)
            ->where('is_processed', false)
            ->whereNull('processing_error')
            ->firstOrFail();
        
        // Supprimer l'ordre
        $order->delete();
        
        return redirect()->route('game.orders.index')
            ->with('success', 'Ordre annulé avec succès.');
    }
    
    /**
     * Enrichit les données d'un ordre avec des informations supplémentaires selon son type
     *
     * @param Order $order
     * @return array
     */
    private function enrichOrderData(Order $order)
    {
        $data = $order->toArray();
        
        // Ajouter des informations supplémentaires selon le type d'ordre
        switch ($order->order_type) {
            case 'move':
                // Ajouter des coordonnées pour la carte
                $data['parameters']['map_coords'] = [
                    'origin' => [
                        'x' => 20,
                        'y' => 50
                    ],
                    'destination' => [
                        'x' => 80,
                        'y' => 50
                    ],
                    'route_length' => 60,
                    'route_angle' => 0
                ];
                break;
                
            case 'colonize':
                // Ajouter des détails sur la planète
                $data['parameters']['planet_type'] = 'Terrestre';
                $data['parameters']['planet_size'] = 'Moyenne';
                $data['parameters']['planet_gravity'] = 1.2;
                $data['parameters']['planet_temperature'] = 22;
                $data['parameters']['habitability'] = 85;
                $data['parameters']['resources'] = [
                    'minerals' => 'Abondant',
                    'crystals' => 'Moyen',
                    'gas' => 'Faible',
                    'radioactives' => 'Trace'
                ];
                $data['parameters']['initial_population'] = 5000;
                $data['parameters']['establishment_time'] = 3;
                break;
                
            case 'research':
                // Ajouter des détails sur la technologie
                $data['parameters']['technology_name'] = 'Propulsion à Impulsion Avancée';
                $data['parameters']['technology_category'] = 'Propulsion';
                $data['parameters']['current_level'] = 2;
                $data['parameters']['required_points'] = 1200;
                $data['parameters']['estimated_turns'] = 3;
                $data['parameters']['benefits'] = 'Augmente la vitesse des vaisseaux de 20% et réduit la consommation de carburant de 15%.';
                break;
                
            case 'build':
                // Ajouter des détails sur la construction
                if ($data['parameters']['build_type'] === 'building') {
                    $data['parameters']['name'] = 'Centre de Recherche';
                    $data['parameters']['building_type'] = 'Recherche';
                    $data['parameters']['level'] = 3;
                    $data['parameters']['size'] = 250;
                    $data['parameters']['energy_required'] = 50;
                    $data['parameters']['effects'] = 'Augmente la production de points de recherche de 30 par tour.';
                } else {
                    $data['parameters']['name'] = 'Croiseur Lourd';
                    $data['parameters']['ship_class'] = 'Croiseur';
                    $data['parameters']['size'] = 800;
                    $data['parameters']['crew_required'] = 120;
                    $data['parameters']['maintenance_cost'] = 45;
                    $data['parameters']['components'] = [
                        [
                            'name' => 'Moteur à Impulsion Mk III',
                            'type' => 'Propulsion',
                            'level' => 3
                        ],
                        [
                            'name' => 'Laser à Plasma',
                            'type' => 'Arme',
                            'level' => 2
                        ],
                        [
                            'name' => 'Bouclier Énergétique',
                            'type' => 'Défense',
                            'level' => 2
                        ]
                    ];
                }
                
                $data['parameters']['location_name'] = 'Terra Prime';
                $data['parameters']['estimated_turns'] = 4;
                $data['parameters']['costs'] = [
                    'credits' => 5000,
                    'minerals' => 2000,
                    'crystals' => 1000,
                    'gas' => 500
                ];
                break;
                
            case 'diplomatic':
                // Ajouter des détails sur la proposition diplomatique
                $data['parameters']['target_commander_name'] = 'Commandant Xenos';
                $data['parameters']['status'] = 'pending';
                
                if ($data['parameters']['diplomatic_type'] === 'trade_proposal') {
                    $data['parameters']['offer'] = [
                        [
                            'type' => 'Minéraux',
                            'amount' => 1000,
                            'unit' => 'unités'
                        ],
                        [
                            'type' => 'Crédits',
                            'amount' => 2000,
                            'unit' => 'crédits'
                        ]
                    ];
                    
                    $data['parameters']['request'] = [
                        [
                            'type' => 'Cristaux',
                            'amount' => 500,
                            'unit' => 'unités'
                        ]
                    ];
                }
                break;
        }
        
        return $data;
    }
}
