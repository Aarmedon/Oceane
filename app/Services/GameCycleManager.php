<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\Order;
use App\Models\TurnReport;
use App\Models\GameEvent;
use App\Models\GameState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GameCycleManager
{
    protected $fleetManager;
    protected $commandantManager;
    protected $technologyManager;
    protected $enrollmentManager;
    protected $currentTurn;

    /**
     * Constructeur avec injection des services nécessaires
     */
    public function __construct(
        FleetManager $fleetManager,
        CommandantManager $commandantManager,
        TechnologyManager $technologyManager,
        EnrollmentManager $enrollmentManager
    ) {
        $this->fleetManager = $fleetManager;
        $this->commandantManager = $commandantManager;
        $this->technologyManager = $technologyManager;
        $this->enrollmentManager = $enrollmentManager;
        // Charger l'état de jeu (tour courant) depuis la base
        $state = GameState::query()->first();
        if (!$state) {
            $state = GameState::create([
                'current_turn' => 1,
                'last_resolved_at' => null,
                'next_turn_at' => now()->addHours(config('oceane.game.hours_between_turns', 24)),
            ]);
        }
        $this->currentTurn = (int) $state->current_turn;
    }

    /**
     * Exécuter un tour de jeu complet
     */
    public function executeTurn(): void
    {
        DB::beginTransaction();
        
        try {
            // Verrouiller l'état de jeu pour empêcher des exécutions concurrentes
            $state = GameState::query()->lockForUpdate()->first();
            if ($state) {
                $this->currentTurn = (int) $state->current_turn;
            }

            Log::info("Début de l'exécution du tour {$this->currentTurn}");
            
            // 0. Suppressions planifiées des flottes
            $this->fleetManager->processScheduledDeletions($this->currentTurn);

            // 1. Traitement des déplacements de flottes
            $this->fleetManager->processFleetMovements($this->currentTurn);
            
            // 2. Traitement des recherches technologiques
            $this->technologyManager->processTechnologyResearch($this->currentTurn);
            
            // 3. Traitement des ordres planifiés pour ce tour
            $this->processScheduledOrders();
            
            // 4. Mise à jour des budgets pour tous les commandants
            $this->updateAllBudgets();
            
            // 5. Traitement des demandes d'inscription/départ (avant les rapports pour figurer dans les rapports du tour)
            $this->enrollmentManager->processPendingAtTurn($this->currentTurn);

            // 6. Génération des rapports de tour
            $this->generateTurnReports();
            
            // 7. Mise à jour du numéro de tour
            $this->incrementTurnNumber();
            
            DB::commit();
            
            Log::info("Fin de l'exécution du tour {$this->currentTurn}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur lors de l'exécution du tour {$this->currentTurn}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Traiter tous les ordres programmés pour ce tour
     */
    private function processScheduledOrders(): void
    {
        $orders = Order::where('turn_execution', $this->currentTurn)
                       ->where('is_processed', false)
                       ->orderBy('id')
                       ->get();
        
        Log::info("{$orders->count()} ordres à traiter pour le tour {$this->currentTurn}");
        
        foreach ($orders as $order) {
            $this->processOrder($order);
        }
    }
    
    /**
     * Traiter un ordre spécifique
     */
    private function processOrder(Order $order): void
    {
        try {
            $commander = $order->commander;
            
            // Traiter l'ordre selon son type
            switch ($order->order_type) {
                case 'move_fleet':
                    $this->processMoveFleetOrder($order, $commander);
                    break;
                    
                case 'research_technology':
                    $this->processResearchTechnologyOrder($order, $commander);
                    break;
                    
                case 'build_ship':
                    $this->processBuildShipOrder($order, $commander);
                    break;
                    
                case 'colonize_planet':
                    $this->processColonizePlanetOrder($order, $commander);
                    break;
                    
                case 'construct_building':
                    $this->processConstructBuildingOrder($order, $commander);
                    break;
                    
                case 'transfer_resources':
                    $this->processTransferResourcesOrder($order, $commander);
                    break;
                    
                // Autres types d'ordres à implémenter selon le jeu
            }
            
            // Marquer l'ordre comme traité
            $order->update([
                'is_processed' => true
            ]);
            
            Log::info("Ordre {$order->id} ({$order->order_type}) traité avec succès");
            
        } catch (\Exception $e) {
            // Enregistrer l'erreur et continuer avec les autres ordres
            $order->update([
                'is_processed' => true,
                'processing_error' => $e->getMessage()
            ]);
            
            Log::error("Erreur lors du traitement de l'ordre {$order->id} ({$order->order_type}): {$e->getMessage()}");
        }
    }
    
    /**
     * Traiter un ordre de déplacement de flotte
     */
    private function processMoveFleetOrder(Order $order, Commander $commander): void
    {
        $params = $order->parameters;
        
        if (!isset($params['fleet_id']) || !isset($params['destination_system_id'])) {
            throw new \Exception("Paramètres incomplets pour l'ordre de déplacement de flotte");
        }
        
        $fleet = $commander->fleets()->findOrFail($params['fleet_id']);
        $destinationSystem = \App\Models\StarSystem::findOrFail($params['destination_system_id']);
        
        $this->fleetManager->moveFleet($fleet, $destinationSystem, $this->currentTurn);
    }
    
    /**
     * Traiter un ordre de recherche technologique
     */
    private function processResearchTechnologyOrder(Order $order, Commander $commander): void
    {
        $params = $order->parameters;
        
        if (!isset($params['technology_id'])) {
            throw new \Exception("Paramètres incomplets pour l'ordre de recherche technologique");
        }
        
        $technology = \App\Models\Technology::findOrFail($params['technology_id']);
        
        $this->technologyManager->startResearch($commander, $technology);
    }
    
    /**
     * Traiter un ordre de construction de vaisseau
     */
    private function processBuildShipOrder(Order $order, Commander $commander): void
    {
        $params = $order->parameters;
        
        if (!isset($params['fleet_id']) || !isset($params['ship_design_id'])) {
            throw new \Exception("Paramètres incomplets pour l'ordre de construction de vaisseau");
        }
        
        $fleet = $commander->fleets()->findOrFail($params['fleet_id']);
        $shipDesign = \App\Models\ShipDesign::findOrFail($params['ship_design_id']);
        $shipName = $params['ship_name'] ?? null;
        $quantity = isset($params['quantity']) ? max(1, (int) $params['quantity']) : 1;
        
        // Utiliser addShipsToFleet si quantité > 1 ou si on est en mode stack_only pour un design stackable
        $mode = config('oceane.fleet.stacking_mode', 'stack_and_row');
        if ($quantity > 1 || ($shipDesign->is_stackable && $mode === 'stack_only')) {
            $this->fleetManager->addShipsToFleet($fleet, $shipDesign, $quantity, $shipName);
        } else {
            $this->fleetManager->addShipToFleet($fleet, $shipDesign, $shipName);
        }
    }
    
    /**
     * Traiter un ordre de colonisation de planète
     */
    private function processColonizePlanetOrder(Order $order, Commander $commander): void
    {
        // TODO: Implémenter la colonisation de planète
    }
    
    /**
     * Traiter un ordre de construction de bâtiment
     */
    private function processConstructBuildingOrder(Order $order, Commander $commander): void
    {
        // TODO: Implémenter la construction de bâtiment
    }
    
    /**
     * Traiter un ordre de transfert de ressources
     */
    private function processTransferResourcesOrder(Order $order, Commander $commander): void
    {
        // TODO: Implémenter le transfert de ressources
    }
    
    /**
     * Mettre à jour les budgets de tous les commandants
     */
    private function updateAllBudgets(): void
    {
        $commanders = Commander::all();
        
        foreach ($commanders as $commander) {
            $this->commandantManager->updateBudget($commander, $this->currentTurn);
        }
        
        Log::info("Budgets mis à jour pour {$commanders->count()} commandants");
    }
    
    /**
     * Générer des rapports de tour pour tous les commandants
     */
    private function generateTurnReports(): void
    {
        $commanders = Commander::all();
        
        foreach ($commanders as $commander) {
            $this->generateCommanderTurnReport($commander);
        }
        
        Log::info("Rapports de tour générés pour {$commanders->count()} commandants");
    }
    
    /**
     * Générer un rapport de tour pour un commandant
     */
    private function generateCommanderTurnReport(Commander $commander): void
    {
        // Récupérer les événements du tour concernant ce commandant
        $events = GameEvent::where('turn_number', $this->currentTurn)
                          ->whereJsonContains('involved_commanders', $commander->id)
                          ->get();
        
        // Préparer le résumé du rapport
        $summary = "Rapport du tour {$this->currentTurn} pour le commandant {$commander->name}.\n\n";
        
        // Ajouter le résumé budgétaire
        $summary .= "== Rapport financier ==\n";
        $summary .= "Crédits précédents: " . number_format($commander->credits, 2) . " cr\n";
        // Ajout d'autres informations financières
        
        // Ajouter le résumé des événements
        if ($events->count() > 0) {
            $summary .= "\n== Événements du tour ==\n";
            foreach ($events as $event) {
                $summary .= "- " . $this->formatEventSummary($event, $commander) . "\n";
            }
        }
        
        // Créer le rapport de tour (idempotent si déjà existant pour ce couple commander/turn)
        TurnReport::firstOrCreate(
            [
                'commander_id' => $commander->id,
                'turn_number' => $this->currentTurn,
            ],
            [
                'summary' => $summary,
                'financial_report' => [], // À compléter avec des données financières détaillées
                'is_read' => false,
            ]
        );
        
        // Notifier le commandant (notification email, etc.)
        // TODO: Implémenter les notifications
    }
    
    /**
     * Formater un résumé d'événement pour un rapport
     */
    private function formatEventSummary(GameEvent $event, Commander $commander): string
    {
        $summary = "";
        
        switch ($event->event_type) {
            case 'space_combat':
                $summary = "Combat spatial dans le système " . $event->event_data['system_name'];
                break;
                
            case 'planet_colonization':
                $summary = "Colonisation d'une planète dans le système " . $event->event_data['system_name'];
                break;
                
            case 'technology_discovery':
                $summary = "Découverte de la technologie " . $event->event_data['technology_name'];
                break;
                
            case 'diplomatic_event':
                $summary = "Événement diplomatique avec " . $event->event_data['other_commander_name'];
                break;
                
            default:
                $summary = "Événement inconnu";
        }
        
        return $summary;
    }
    
    /**
     * Incrémenter le numéro de tour
     */
    private function incrementTurnNumber(): void
    {
        // Mise à jour persistante du tour dans la base (dans la même transaction)
        $state = GameState::query()->lockForUpdate()->first();
        if (!$state) {
            $state = GameState::create([
                'current_turn' => 1,
                'last_resolved_at' => null,
                'next_turn_at' => now()->addHours(config('oceane.game.hours_between_turns', 24)),
            ]);
        }
        $newTurn = ((int) $this->currentTurn) + 1;
        $state->current_turn = $newTurn;
        $state->last_resolved_at = now();
        $state->next_turn_at = now()->addHours(config('oceane.game.hours_between_turns', 24));
        $state->save();
        
        $this->currentTurn = $newTurn;
        Log::info("Tour incrémenté à {$this->currentTurn}");
    }
    
    /**
     * Planifier le prochain tour automatique
     */
    public function scheduleNextTurn(): void
    {
        // Cette méthode pourrait configurer une tâche planifiée pour exécuter 
        // automatiquement le prochain tour à un moment précis
        
        $nextTurnTime = now()->addHours(config('oceane.game.hours_between_turns', 24));
        
        // Utiliser le système de planification de tâches de Laravel
        // Par exemple via une commande Artisan programmée
        
        Log::info("Prochain tour ({$this->currentTurn}) programmé pour {$nextTurnTime}");
    }
}
