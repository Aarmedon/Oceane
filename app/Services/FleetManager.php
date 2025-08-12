<?php

namespace App\Services;

use App\Models\Fleet;
use App\Models\Ship;
use App\Models\FleetShipStack;
use App\Models\Commander;
use App\Models\StarSystem;
use App\Models\Planet;
use App\Models\Hero;
use App\Models\ShipDesign;
use App\Models\CombatReport;
use App\Models\GameEvent;
use App\Models\GameState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FleetManager
{
    /**
     * Créer une nouvelle flotte pour un commandant
     */
    public function createFleet(Commander $commander, StarSystem $system, string $name): Fleet
    {
        // Vérifier que le commandant a accès au système (possède le système ou une flotte à cet endroit)
        $hasAccess = $system->commander_id == $commander->id || 
                    $commander->fleets()->where('current_system_id', $system->id)->exists();
        
        if (!$hasAccess) {
            throw new \Exception("Le commandant n'a pas accès à ce système pour créer une flotte.");
        }
        
        $fleet = Fleet::create([
            'commander_id' => $commander->id,
            'name' => $name,
            'current_system_id' => $system->id,
            'position_x' => $system->position_x,
            'position_y' => $system->position_y,
            'galaxy_id' => $system->sector->galaxy_id,
            'status' => Fleet::STATUS_DOCKED,
            'morale' => config('oceane.fleet.default_morale', 100),
            'experience' => 0,
            'maintenance_cost' => 0,
            'directive_id' => config('oceane.directives.patrol', 0)
        ]);
        
        Log::info("Flotte {$name} créée pour le commandant {$commander->name} dans le système {$system->name}");
        
        return $fleet;
    }
    
    /**
     * Ajouter un vaisseau à une flotte
     */
    public function addShipToFleet(Fleet $fleet, ShipDesign $shipDesign, ?string $shipName = null, bool $asGameMaster = false): Ship
    {
        return DB::transaction(function () use ($fleet, $shipDesign, $shipName, $asGameMaster) {
            // Verrouiller les lignes critiques
            $fleetLocked = Fleet::where('id', $fleet->id)->lockForUpdate()->firstOrFail();
            $commanderLocked = $fleetLocked->commander()->lockForUpdate()->first();

            // Vérifier crédits sous verrou
            if (!$asGameMaster) {
                if (($commanderLocked->credits ?? 0) < $shipDesign->base_cost) {
                    throw new \Exception("Crédits insuffisants pour construire ce vaisseau.");
                }
            }

            // Générer un nom de vaisseau par défaut si non spécifié
            $finalName = $shipName;
            if (!$finalName) {
                $countExisting = Ship::where('fleet_id', $fleetLocked->id)->lockForUpdate()->count();
                $finalName = $fleetLocked->name . '-' . ($countExisting + 1);
            }

            // Créer le nouveau vaisseau
            $ship = Ship::create([
                'fleet_id' => $fleetLocked->id,
                'ship_design_id' => $shipDesign->id,
                'name' => $finalName,
                'hull_points' => $shipDesign->max_hull_points,
                'max_hull_points' => $shipDesign->max_hull_points,
                'shield_points' => $shipDesign->max_shield_points,
                'max_shield_points' => $shipDesign->max_shield_points,
                'experience' => 0,
                'damage_level' => 0,
                'status' => Ship::STATUS_OPERATIONAL
            ]);

            // Hybride: si le design est empilable, maintenir/mettre à jour la stack correspondante (sous verrou)
            if ($shipDesign->is_stackable) {
                $stack = FleetShipStack::where('fleet_id', $fleetLocked->id)
                    ->where('ship_design_id', $shipDesign->id)
                    ->lockForUpdate()
                    ->first();
                if (!$stack) {
                    $stack = FleetShipStack::create([
                        'fleet_id' => $fleetLocked->id,
                        'ship_design_id' => $shipDesign->id,
                        'count_operational' => 0,
                        'count_damaged' => 0,
                        'count_destroyed' => 0,
                    ]);
                }
                $stack->increment('count_operational');
            }

            // Mettre à jour le coût d'entretien de la flotte (sous verrou)
            $fleetLocked->increment('maintenance_cost', $shipDesign->maintenance_cost);

            // Déduire le coût du vaisseau des crédits du commandant (sauf MJ)
            if (!$asGameMaster) {
                $commanderLocked->decrement('credits', $shipDesign->base_cost);
            }

            // Installer les composants du vaisseau
            foreach ($shipDesign->components as $component) {
                $ship->components()->attach($component->id, [
                    'quantity' => $component->pivot->quantity,
                    'status' => 'operational'
                ]);
            }

            Log::info("Vaisseau {$finalName} ajouté à la flotte {$fleetLocked->name}");

            return $ship;
        }, 3);
    }
    
    /**
     * Ajouter plusieurs vaisseaux à une flotte (mode hybride/bulk)
     * - Si le design est stackable et le mode est 'stack_only', n'insère pas de lignes Ship
     *   mais met à jour la stack et les coûts (entretien/crédits).
     * - Sinon, fallback sur des insertions unitaires (réutilise addShipToFleet).
     */
    public function addShipsToFleet(Fleet $fleet, ShipDesign $shipDesign, int $count, ?string $baseName = null, bool $asGameMaster = false): void
    {
        if ($count <= 0) {
            return;
        }
        
        $mode = config('oceane.fleet.stacking_mode', 'stack_and_row');
        $commander = $fleet->commander;
        
        $totalCost = (float) ($shipDesign->base_cost * $count);
        if (!$asGameMaster) {
            if ($commander->credits < $totalCost) {
                throw new \Exception("Crédits insuffisants pour construire {$count} vaisseaux.");
            }
        }
        
        if ($shipDesign->is_stackable && $mode === 'stack_only') {
            // Effectuer l'opération en une transaction atomique avec verrous
            DB::transaction(function () use ($fleet, $shipDesign, $count, $totalCost, $asGameMaster) {
                $fleetLocked = Fleet::where('id', $fleet->id)->lockForUpdate()->firstOrFail();
                $commanderLocked = $fleetLocked->commander()->lockForUpdate()->first();

                if (!$asGameMaster) {
                    if (($commanderLocked->credits ?? 0) < $totalCost) {
                        throw new \Exception("Crédits insuffisants pour construire {$count} vaisseaux.");
                    }
                }

                $stack = FleetShipStack::where('fleet_id', $fleetLocked->id)
                    ->where('ship_design_id', $shipDesign->id)
                    ->lockForUpdate()
                    ->first();
                if (!$stack) {
                    $stack = FleetShipStack::create([
                        'fleet_id' => $fleetLocked->id,
                        'ship_design_id' => $shipDesign->id,
                        'count_operational' => 0,
                        'count_damaged' => 0,
                        'count_destroyed' => 0,
                    ]);
                }

                $stack->increment('count_operational', $count);

                // Mettre à jour maintenance
                $fleetLocked->increment('maintenance_cost', (float) ($shipDesign->maintenance_cost * $count));

                // Déduire crédits en une fois (sauf MJ)
                if (!$asGameMaster) {
                    $commanderLocked->decrement('credits', $totalCost);
                }

                Log::info("{$count} {$shipDesign->name} ajoutés en stack_only à la flotte {$fleetLocked->name}");
            }, 3);
            return;
        }
        
        // Fallback: mode stack_and_row ou design non stackable => créer des lignes Ship
        if ($count === 1) {
            $this->addShipToFleet($fleet, $shipDesign, $baseName, $asGameMaster);
            return;
        }
        // Exécuter l'ajout en une seule transaction pour éviter les interleavings concurrents
        DB::transaction(function () use ($fleet, $shipDesign, $count, $baseName, $asGameMaster, $totalCost) {
            $fleetLocked = Fleet::where('id', $fleet->id)->lockForUpdate()->firstOrFail();
            $commanderLocked = $fleetLocked->commander()->lockForUpdate()->first();

            // Vérifier et déduire les crédits une fois (sauf MJ)
            if (!$asGameMaster) {
                if (($commanderLocked->credits ?? 0) < $totalCost) {
                    throw new \Exception("Crédits insuffisants pour construire {$count} vaisseaux.");
                }
                $commanderLocked->decrement('credits', $totalCost);
            }

            // Ajouter les vaisseaux un par un sous le même verrou, en bypassant la vérif de crédits interne
            for ($i = 0; $i < $count; $i++) {
                $name = $baseName ? ($baseName . '-' . ($i + 1)) : null;
                $this->addShipToFleet($fleetLocked, $shipDesign, $name, true);
            }
        }, 3);
    }

    /**
     * Retirer plusieurs vaisseaux d'une flotte (par design)
     * - Privilégie la décrémentation des stacks si elles existent
     * - Complète en supprimant des lignes Ship si nécessaire
     * - Ajuste le coût d'entretien; pas de remboursement de crédits
     */
    public function removeShipsFromFleet(Fleet $fleet, ShipDesign $shipDesign, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        DB::transaction(function () use ($fleet, $shipDesign, $count) {
            // Verrouiller la flotte et la stack correspondante
            $fleetLocked = Fleet::where('id', $fleet->id)->lockForUpdate()->firstOrFail();
            $stack = FleetShipStack::where('fleet_id', $fleetLocked->id)
                ->where('ship_design_id', $shipDesign->id)
                ->lockForUpdate()
                ->first();

            // Calculer le disponible: stacks (op + dmg) + ships non détruits (sous verrou)
            $availableInStack = $stack ? ((int)$stack->count_operational + (int)$stack->count_damaged) : 0;
            $availableInShips = Ship::where('fleet_id', $fleetLocked->id)
                ->where('ship_design_id', $shipDesign->id)
                ->where('status', '!=', Ship::STATUS_DESTROYED)
                ->lockForUpdate()
                ->count();

            $totalAvailable = $availableInStack + $availableInShips;
            if ($totalAvailable < $count) {
                throw new \Exception("Impossible de retirer {$count} vaisseaux: seulement {$totalAvailable} disponibles pour ce design.");
            }

            $toRemove = $count;
            // 1) Décrémenter la stack si présente
            if ($stack && $toRemove > 0) {
                // D'abord opérationnels, puis endommagés
                $removeFromOperational = min($toRemove, (int)$stack->count_operational);
                if ($removeFromOperational > 0) {
                    $stack->decrement('count_operational', $removeFromOperational);
                    $toRemove -= $removeFromOperational;
                }

                $removeFromDamaged = min($toRemove, (int)$stack->count_damaged);
                if ($removeFromDamaged > 0) {
                    $stack->decrement('count_damaged', $removeFromDamaged);
                    $toRemove -= $removeFromDamaged;
                }

                // Nettoyage si tout est à zéro
                $stack->refresh();
                if ($stack->count_operational <= 0 && $stack->count_damaged <= 0 && $stack->count_destroyed <= 0) {
                    $stack->delete();
                }
            }

            // 2) Supprimer des lignes Ship si nécessaire (opérationnels d'abord, puis endommagés)
            if ($toRemove > 0) {
                $shipIds = Ship::where('fleet_id', $fleetLocked->id)
                    ->where('ship_design_id', $shipDesign->id)
                    ->whereIn('status', [Ship::STATUS_OPERATIONAL, Ship::STATUS_DAMAGED, Ship::STATUS_CRITICAL])
                    ->orderByRaw("FIELD(status, 'operational','damaged','critical')")
                    ->limit($toRemove)
                    ->lockForUpdate()
                    ->pluck('id');
                if ($shipIds->count() < $toRemove) {
                    throw new \Exception('Incohérence: quantité à retirer supérieure aux unités trouvées.');
                }
                // Supprimer les composants pivot, puis les ships
                foreach ($shipIds as $sid) {
                    /** @var Ship $ship */
                    $ship = Ship::find($sid);
                    if ($ship) {
                        $ship->components()->detach();
                        $ship->delete();
                    }
                }
                $toRemove = 0;
            }

            // 3) Ajuster le coût d'entretien (aucun remboursement de crédits) avec garde anti-négatif
            $decrease = (float) ($shipDesign->maintenance_cost * $count);
            $current = (float) $fleetLocked->maintenance_cost;
            $newValue = $current - $decrease;
            if ($newValue < 0) {
                $newValue = 0.0;
            }
            $fleetLocked->update(['maintenance_cost' => $newValue]);

            Log::info("{$count} {$shipDesign->name} retirés de la flotte {$fleetLocked->name}");
        }, 3);
    }
    
    /**
     * Obtenir la composition d'une flotte par design via les stacks si disponibles (fallback ships)
     * Retourne un tableau: [ design_id => ['design' => ShipDesign, 'operational' => int, 'damaged' => int, 'destroyed' => int] ]
     */
    public function getFleetComposition(Fleet $fleet): array
    {
        $result = [];
        $stacks = $fleet->relationLoaded('shipStacks')
            ? $fleet->shipStacks
            : $fleet->shipStacks()->with('shipDesign')->get();
        if ($stacks->isNotEmpty()) {
            foreach ($stacks as $stack) {
                $result[$stack->ship_design_id] = [
                    'design' => $stack->shipDesign,
                    'operational' => (int) $stack->count_operational,
                    'damaged' => (int) $stack->count_damaged,
                    'destroyed' => (int) $stack->count_destroyed,
                ];
            }
            // Merge in non-stacked ships (e.g., capitals) for designs not present in stacks
            $stackDesignIds = array_keys($result);
            $shipsQueryBase = $fleet->relationLoaded('ships') ? $fleet->ships : $fleet->ships()->with('shipDesign')->get();
            $ships = collect($shipsQueryBase);
            if (!empty($stackDesignIds)) {
                $ships = $ships->whereNotIn('ship_design_id', $stackDesignIds);
            }
            foreach ($ships->groupBy('ship_design_id') as $designId => $group) {
                $design = optional($group->first())->shipDesign;
                $operational = $group->where('status', Ship::STATUS_OPERATIONAL)->count();
                $damaged = $group->whereIn('status', [Ship::STATUS_DAMAGED, Ship::STATUS_CRITICAL, 'repairing'])->count();
                $destroyed = $group->where('status', Ship::STATUS_DESTROYED)->count();
                $result[$designId] = [
                    'design' => $design,
                    'operational' => (int) $operational,
                    'damaged' => (int) $damaged,
                    'destroyed' => (int) $destroyed,
                ];
            }
            return $result;
        }

        // Fallback: agréger depuis ships uniquement
        $ships = $fleet->relationLoaded('ships') ? $fleet->ships : $fleet->ships()->with('shipDesign')->get();
        foreach ($ships->groupBy('ship_design_id') as $designId => $group) {
            $design = optional($group->first())->shipDesign;
            $operational = $group->where('status', Ship::STATUS_OPERATIONAL)->count();
            // Regrouper les autres statuts en "damaged" pour l'agrégat
            $damaged = $group->whereIn('status', [Ship::STATUS_DAMAGED, Ship::STATUS_CRITICAL, 'repairing'])->count();
            $destroyed = $group->where('status', Ship::STATUS_DESTROYED)->count();
            $result[$designId] = [
                'design' => $design,
                'operational' => (int) $operational,
                'damaged' => (int) $damaged,
                'destroyed' => (int) $destroyed,
            ];
        }

        return $result;
    }
    
    /**
     * Déplacer une flotte vers un système de destination
     */
    public function moveFleet(Fleet $fleet, StarSystem $destination, int $currentTurn): void
    {
        // Vérifier que la flotte n'est pas déjà en mouvement
        if ($fleet->status == Fleet::STATUS_MOVING) {
            throw new \Exception("La flotte est déjà en mouvement.");
        }
        
        // Calculer la distance et le temps de voyage
        $source = $fleet->currentSystem;
        $distance = $this->calculateDistance($source, $destination);
        $travelTime = $this->calculateTravelTime($fleet, $distance);
        
        // Vérifier si le voyage implique un changement de galaxie
        $isIntergalacticTravel = $source->sector->galaxy_id != $destination->sector->galaxy_id;
        if ($isIntergalacticTravel) {
            // Vérifier si la flotte a la capacité de voyage intergalactique
            if (!$this->hasIntergalacticCapability($fleet)) {
                throw new \Exception("Cette flotte n'a pas la capacité de voyage intergalactique.");
            }
            
            // Les voyages intergalactiques prennent plus de temps
            $travelTime *= config('oceane.travel.intergalactic_multiplier', 2);
        }
        
        // Mettre à jour la flotte
        $fleet->update([
            'destination_system_id' => $destination->id,
            'status' => Fleet::STATUS_MOVING,
            'arrival_turn' => $currentTurn + $travelTime
        ]);
        
        Log::info("Flotte {$fleet->name} en route vers {$destination->name}, arrivée prévue au tour {$fleet->arrival_turn}");
    }
    
    /**
     * Calculer la distance entre deux systèmes stellaires
     */
    private function calculateDistance(StarSystem $source, StarSystem $destination): float
    {
        // Si les systèmes sont dans différentes galaxies, on utilise les coordonnées des portes galactiques
        if ($source->sector->galaxy_id != $destination->sector->galaxy_id) {
            // TODO: Implémenter le calcul via les portes galactiques
            return 100; // Valeur temporaire
        }
        
        // Calcul de distance euclidienne standard
        $dx = $destination->position_x - $source->position_x;
        $dy = $destination->position_y - $source->position_y;
        
        return sqrt($dx * $dx + $dy * $dy);
    }
    
    /**
     * Calculer le temps de voyage basé sur la distance et la vitesse de la flotte
     */
    private function calculateTravelTime(Fleet $fleet, float $distance): int
    {
        // Calculer la vitesse maximale de la flotte (basée sur le vaisseau le plus lent)
        $speed = $this->calculateFleetSpeed($fleet);
        
        // Calculer le temps de voyage en tours
        $travelTime = ceil($distance / $speed);
        
        // Le temps de voyage minimum est de 1 tour
        return max(1, $travelTime);
    }
    
    /**
     * Calculer la vitesse d'une flotte (limitée par le vaisseau le plus lent)
     */
    private function calculateFleetSpeed(Fleet $fleet): float
    {
        $minSpeed = PHP_FLOAT_MAX;
        
        foreach ($fleet->ships as $ship) {
            $shipSpeed = $this->calculateShipSpeed($ship);
            $minSpeed = min($minSpeed, $shipSpeed);
        }
        
        // Si la flotte n'a pas de vaisseaux (ce qui ne devrait pas arriver), utiliser une vitesse par défaut
        if ($minSpeed == PHP_FLOAT_MAX) {
            $minSpeed = config('oceane.fleet.default_speed', 5);
        }
        
        // Bonus de vitesse si un héros est assigné à la flotte
        if ($fleet->hero) {
            $speedBonus = 1 + ($fleet->hero->combat_skill * 0.05);
            $minSpeed *= $speedBonus;
        }
        
        return $minSpeed;
    }
    
    /**
     * Calculer la vitesse d'un vaisseau
     */
    private function calculateShipSpeed(Ship $ship): float
    {
        // Obtenir le composant de propulsion avec la plus grande valeur de vitesse
        $propulsionComponent = $ship->components()
            ->where('type', 'moteur')
            ->orderByDesc('weapon_speed') // Nous réutilisons ce champ pour la vitesse du moteur
            ->first();
        
        if (!$propulsionComponent) {
            return config('oceane.ship.default_speed', 3);
        }
        
        // La vitesse de base dépend du composant
        $baseSpeed = $propulsionComponent->weapon_speed;
        
        // Facteur de dommage (un vaisseau endommagé est plus lent)
        $damageRatio = $ship->hull_points / $ship->max_hull_points;
        
        return $baseSpeed * $damageRatio;
    }
    
    /**
     * Vérifier si une flotte a la capacité de voyage intergalactique
     */
    private function hasIntergalacticCapability(Fleet $fleet): bool
    {
        foreach ($fleet->ships as $ship) {
            $hasCapability = $ship->components()
                ->where('special_characteristics->intergalactic_travel', true)
                ->exists();
                
            if ($hasCapability) {
                return true;
            }
        }
        
        // Un héros avec la capacité de voyage intergalactique peut aussi permettre ce type de voyage
        if ($fleet->hero && $fleet->hero->hasCompetence('voyage_intergalactique')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Traiter les déplacements de toutes les flottes pour un tour donné
     */
    public function processFleetMovements(int $currentTurn): void
    {
        // Récupérer toutes les flottes qui doivent arriver à ce tour
        $arrivingFleets = Fleet::where('status', Fleet::STATUS_MOVING)
                              ->where('arrival_turn', $currentTurn)
                              ->get();
        
        foreach ($arrivingFleets as $fleet) {
            $this->processFleetArrival($fleet);
        }
        
        Log::info("{$arrivingFleets->count()} flottes sont arrivées à destination au tour {$currentTurn}");
    }
    
    /**
     * Traiter l'arrivée d'une flotte à destination
     */
    private function processFleetArrival(Fleet $fleet): void
    {
        $destination = $fleet->destinationSystem;
        
        // Mettre à jour la position et le statut de la flotte
        $fleet->update([
            'current_system_id' => $destination->id,
            'destination_system_id' => null,
            'position_x' => $destination->position_x,
            'position_y' => $destination->position_y,
            'galaxy_id' => $destination->sector->galaxy_id,
            'status' => Fleet::STATUS_DOCKED
        ]);
        
        Log::info("Flotte {$fleet->name} arrivée au système {$destination->name}");
        
        // Vérifier s'il y a des flottes hostiles pour initier un combat
        $this->checkForHostileEncounters($fleet, $destination);
        
        // Exécuter la directive de la flotte si applicable
        if (!is_null($fleet->directive_id)) {
            $this->executeFleetDirective($fleet);
        }
    }
    
    /**
     * Vérifier la présence de flottes hostiles à l'arrivée
     */
    private function checkForHostileEncounters(Fleet $fleet, StarSystem $system): void
    {
        // Récupérer toutes les autres flottes dans ce système
        $otherFleets = Fleet::where('current_system_id', $system->id)
                          ->where('commander_id', '!=', $fleet->commander_id)
                          ->where('status', '!=', Fleet::STATUS_MOVING)
                          ->get();
        
        if ($otherFleets->isEmpty()) {
            return;
        }
        
        // Vérifier pour chaque flotte si elle est hostile
        $hostileFleets = new Collection();
        
        foreach ($otherFleets as $otherFleet) {
            if ($this->isFleetHostile($otherFleet, $fleet)) {
                $hostileFleets->push($otherFleet);
            }
        }
        
        if ($hostileFleets->isNotEmpty()) {
            // Initier un combat avec les flottes hostiles
            $this->initiateCombat($fleet, $hostileFleets, $system);
        }
    }
    
    /**
     * Vérifier si une flotte est hostile envers une autre
     */
    private function isFleetHostile(Fleet $fleet1, Fleet $fleet2): bool
    {
        // Vérifier si la flotte a une directive d'attaque
        if (in_array($fleet1->directive_id, [
            config('oceane.directives.attack_fleets', 3),
            config('oceane.directives.attack_player', 8)
        ])) {
            return true;
        }
        
        // Vérifier l'état des relations diplomatiques
        $commander1 = $fleet1->commander;
        $commander2 = $fleet2->commander;
        
        // TODO: Implémenter la vérification des relations diplomatiques
        
        return false;
    }
    
    /**
     * Exécuter la directive assignée à une flotte
     */
    private function executeFleetDirective(Fleet $fleet): void
    {
        switch ($fleet->directive_id) {
            case config('oceane.directives.attack_system', 1):
                $this->executeAttackSystemDirective($fleet);
                break;
                
            case config('oceane.directives.attack_planet', 5):
                $this->executeAttackPlanetDirective($fleet);
                break;
                
            case config('oceane.directives.pillage_system', 4):
                $this->executePillageSystemDirective($fleet);
                break;
                
            case config('oceane.directives.pillage_planet', 6):
                $this->executePillagePlanetDirective($fleet);
                break;
                
            case config('oceane.directives.eradicate_planet', 7):
                $this->executeEradicatePlanetDirective($fleet);
                break;
                
            // Ajoutez d'autres cas selon les directives définies
        }
    }
    
    /**
     * Initialiser un combat spatial entre flottes
     */
    private function initiateCombat(Fleet $attackerFleet, Collection $defenderFleets, StarSystem $system): void
    {
        Log::info("Combat spatial initié dans le système {$system->name}");

        DB::transaction(function () use ($attackerFleet, $defenderFleets, $system) {
            // Verrouiller les flottes impliquées
            $attackerLocked = Fleet::where('id', $attackerFleet->id)->lockForUpdate()->firstOrFail();
            $defendersLocked = $defenderFleets->map(function ($f) {
                return Fleet::where('id', $f->id)->lockForUpdate()->first();
            })->filter();

            // Créer un événement de combat
            $event = GameEvent::create([
                'turn_number' => (int) (GameState::query()->value('current_turn') ?? 1),
                'event_type' => 'space_combat',
                'event_data' => [
                    'system_id' => $system->id,
                    'system_name' => $system->name,
                    'attacker_fleet_id' => $attackerLocked->id,
                    'attacker_fleet_name' => $attackerLocked->name,
                    'defender_fleet_ids' => $defendersLocked->pluck('id')->toArray(),
                ],
                'involved_commanders' => array_merge(
                    [$attackerLocked->commander_id],
                    $defendersLocked->pluck('commander_id')->unique()->toArray()
                ),
                'is_public' => false
            ]);

            // Mettre à jour le statut des flottes sous verrou
            $attackerLocked->update(['status' => Fleet::STATUS_COMBAT]);
            foreach ($defendersLocked as $fleet) {
                $fleet->update(['status' => Fleet::STATUS_COMBAT]);
            }

            // Exécuter le combat
            $this->executeCombat($event, $attackerLocked, $defendersLocked);
        }, 3);
    }

    /**
     * Exécuter un combat spatial
     */
    private function executeCombat(GameEvent $event, Fleet $attackerFleet, Collection $defenderFleets): void
    {
        // Préparer le rapport de combat
        $combatReport = new CombatReport();
        $combatReport->game_event_id = $event->id;
        // Charger les relations nécessaires une seule fois
        $attackerFleet->loadMissing(['ships.shipDesign', 'shipStacks.shipDesign', 'commander', 'hero']);
        $defenderFleets->load(['ships.shipDesign', 'shipStacks.shipDesign', 'commander', 'hero']);

        $combatReport->participants = [
            'attacker' => [
                'fleet_id' => $attackerFleet->id,
                'fleet_name' => $attackerFleet->name,
                'commander_id' => $attackerFleet->commander_id,
                'commander_name' => optional($attackerFleet->commander)->name,
                'hero' => [
                    'id' => $attackerFleet->hero_id,
                    'name' => optional($attackerFleet->hero)->name,
                    'level' => optional($attackerFleet->hero)->level,
                    'experience' => optional($attackerFleet->hero)->experience,
                    'combat_skill' => optional($attackerFleet->hero)->combat_skill,
                ],
                'ships' => $attackerFleet->ships->where('status', '!=', Ship::STATUS_DESTROYED)->map(function (Ship $ship) {
                    return [
                        'id' => $ship->id,
                        'name' => $ship->name,
                        'design' => optional($ship->shipDesign)->name,
                        'hull_points' => $ship->hull_points,
                        'max_hull_points' => $ship->max_hull_points,
                        'shield_points' => $ship->shield_points,
                        'max_shield_points' => $ship->max_shield_points,
                        'combat_power' => $ship->combat_power,
                    ];
                })->values()->toArray(),
                'stacks' => $attackerFleet->shipStacks->map(function ($stack) {
                    return [
                        'ship_design_id' => $stack->ship_design_id,
                        'ship_design_name' => optional($stack->shipDesign)->name,
                        'count_operational' => (int) $stack->count_operational,
                        'count_damaged' => (int) $stack->count_damaged,
                        'count_destroyed' => (int) $stack->count_destroyed,
                        'unit_power' => (int) optional($stack->shipDesign)->combat_power,
                    ];
                })->values()->toArray(),
            ],
            'defenders' => $defenderFleets->map(function (Fleet $fleet) {
                return [
                    'fleet_id' => $fleet->id,
                    'fleet_name' => $fleet->name,
                    'commander_id' => $fleet->commander_id,
                    'commander_name' => optional($fleet->commander)->name,
                    'hero' => [
                        'id' => $fleet->hero_id,
                        'name' => optional($fleet->hero)->name,
                        'level' => optional($fleet->hero)->level,
                        'experience' => optional($fleet->hero)->experience,
                        'combat_skill' => optional($fleet->hero)->combat_skill,
                    ],
                    'ships' => $fleet->ships->where('status', '!=', Ship::STATUS_DESTROYED)->map(function (Ship $ship) {
                        return [
                            'id' => $ship->id,
                            'name' => $ship->name,
                            'design' => optional($ship->shipDesign)->name,
                            'hull_points' => $ship->hull_points,
                            'max_hull_points' => $ship->max_hull_points,
                            'shield_points' => $ship->shield_points,
                            'max_shield_points' => $ship->max_shield_points,
                            'combat_power' => $ship->combat_power,
                        ];
                    })->values()->toArray(),
                    'stacks' => $fleet->shipStacks->map(function ($stack) {
                        return [
                            'ship_design_id' => $stack->ship_design_id,
                            'ship_design_name' => optional($stack->shipDesign)->name,
                            'count_operational' => (int) $stack->count_operational,
                            'count_damaged' => (int) $stack->count_damaged,
                            'count_destroyed' => (int) $stack->count_destroyed,
                            'unit_power' => (int) optional($stack->shipDesign)->combat_power,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray(),
        ];

        // Simuler le combat round par round
        $rounds = [];
        $maxRounds = (int) config('oceane.combat.max_rounds', 10);
        $eliminationRate = 0.15; // pourcentage d'unités éliminées par round
        // Modificateurs héros et expérience de flotte pour la puissance de combat
        $heroPowerFactor = (float) config('oceane.combat.hero_combat_power_factor', 0.05);
        $heroPowerMaxBonus = (float) config('oceane.combat.hero_max_bonus', 0.5);
        $fleetExpPowerFactor = (float) config('oceane.combat.fleet_experience_power_factor', 0.001);
        $fleetExpPowerMaxBonus = (float) config('oceane.combat.fleet_experience_max_bonus', 0.5);
        // Expérience des stacks (si colonne présente)
        $stackExpPowerFactor = (float) config('oceane.combat.stack_experience_power_factor', 0.001);
        $stackExpPowerMaxBonus = (float) config('oceane.combat.stack_experience_max_bonus', 0.5);

        // État mutable en mémoire pour le combat
        $state = [
            'attacker' => [
                'fleet' => $attackerFleet,
                'ships' => $attackerFleet->ships->where('status', '!=', Ship::STATUS_DESTROYED)->map(function (Ship $s) {
                    return ['id' => $s->id, 'power' => $s->combat_power, 'hull' => $s->hull_points];
                })->values()->all(),
                'stacks' => collect($attackerFleet->shipStacks)->keyBy('ship_design_id')->map(function ($stack) {
                    return [
                        'unit_power' => (int) optional($stack->shipDesign)->combat_power,
                        'operational' => (int) $stack->count_operational,
                        'damaged' => (int) $stack->count_damaged,
                        'experience' => (int) ($stack->experience ?? 0),
                    ];
                })->toArray(),
            ],
            'defenders' => $defenderFleets->map(function (Fleet $fleet) {
                return [
                    'fleet' => $fleet,
                    'ships' => $fleet->ships->where('status', '!=', Ship::STATUS_DESTROYED)->map(function (Ship $s) {
                        return ['id' => $s->id, 'power' => $s->combat_power, 'hull' => $s->hull_points];
                    })->values()->all(),
                    'stacks' => collect($fleet->shipStacks)->keyBy('ship_design_id')->map(function ($stack) {
                        return [
                            'unit_power' => (int) optional($stack->shipDesign)->combat_power,
                            'operational' => (int) $stack->count_operational,
                            'damaged' => (int) $stack->count_damaged,
                            'experience' => (int) ($stack->experience ?? 0),
                        ];
                    })->toArray(),
                ];
            })->values()->all(),
        ];

        $computeUnits = function ($side) {
            $ships = isset($side['ships']) ? count($side['ships']) : 0;
            $stacks = 0;
            if (isset($side['stacks'])) {
                foreach ($side['stacks'] as $s) { $stacks += ($s['operational'] + $s['damaged']); }
            }
            return $ships + $stacks;
        };
        $computePower = function ($side) use ($heroPowerFactor, $heroPowerMaxBonus, $fleetExpPowerFactor, $fleetExpPowerMaxBonus, $stackExpPowerFactor, $stackExpPowerMaxBonus) {
            $power = 0;
            if (isset($side['ships'])) {
                foreach ($side['ships'] as $s) { $power += (int) $s['power']; }
            }
            if (isset($side['stacks'])) {
                foreach ($side['stacks'] as $s) {
                    $stackExp = (int) ($s['experience'] ?? 0);
                    $stackMultiplier = 1 + min($stackExpPowerMaxBonus, max(0, $stackExp) * $stackExpPowerFactor);
                    $power += (int) floor($s['operational'] * $s['unit_power'] * $stackMultiplier);
                    $power += (int) floor($s['damaged'] * max(1, (int) floor($s['unit_power'] * 0.5)) * $stackMultiplier);
                }
            }
            $power = max(0, $power);
            // Appliquer les multiplicateurs liés au héros et à l'expérience de la flotte
            if (isset($side['fleet']) && $side['fleet'] instanceof Fleet) {
                $fleetObj = $side['fleet'];
                $heroSkill = (int) optional($fleetObj->hero)->combat_skill;
                $heroMultiplier = 1 + min($heroPowerMaxBonus, max(0, $heroSkill) * $heroPowerFactor);
                $fleetExp = (int) $fleetObj->experience;
                $fleetExpMultiplier = 1 + min($fleetExpPowerMaxBonus, max(0, $fleetExp) * $fleetExpPowerFactor);
                $power = (int) floor($power * $heroMultiplier * $fleetExpMultiplier);
            }
            return $power;
        };
        $applyKillsToFleet = function (&$fleetState, int $kills) {
            $destroyedShips = [];
            $stackLosses = []; // design_id => ['operational' => X, 'damaged' => Y]
            $remaining = $kills;
            // Supprimer des vaisseaux individuels d'abord (plus simples à cibler)
            $shipCount = count($fleetState['ships']);
            if ($shipCount > 0 && $remaining > 0) {
                $toRemove = min($remaining, $shipCount);
                $removed = array_splice($fleetState['ships'], 0, $toRemove);
                foreach ($removed as $r) { $destroyedShips[] = $r['id']; }
                $remaining -= $toRemove;
            }
            if ($remaining > 0 && !empty($fleetState['stacks'])) {
                // Détruire des unités stackées: prioriser les opérationnelles
                foreach ($fleetState['stacks'] as $designId => &$s) {
                    if ($remaining <= 0) break;
                    $opKill = min($remaining, $s['operational']);
                    if ($opKill > 0) {
                        $s['operational'] -= $opKill;
                        $remaining -= $opKill;
                        $stackLosses[$designId]['operational'] = ($stackLosses[$designId]['operational'] ?? 0) + $opKill;
                    }
                }
                unset($s);
                if ($remaining > 0) {
                    foreach ($fleetState['stacks'] as $designId => &$s) {
                        if ($remaining <= 0) break;
                        $dmKill = min($remaining, $s['damaged']);
                        if ($dmKill > 0) {
                            $s['damaged'] -= $dmKill;
                            $remaining -= $dmKill;
                            $stackLosses[$designId]['damaged'] = ($stackLosses[$designId]['damaged'] ?? 0) + $dmKill;
                        }
                    }
                    unset($s);
                }
            }
            return ['ships' => $destroyedShips, 'stacks' => $stackLosses, 'unapplied' => $remaining];
        };

        $attackerTotalLosses = 0;
        $defenderTotalLosses = 0;
        $attackerDestroyedShipIds = [];
        $attackerStackLosses = []; // design_id => ['operational'=>x,'damaged'=>y]
        $defendersDestroyedShipIds = []; // fleet_id => [ids]
        $defendersStackLosses = []; // fleet_id => [design_id=> ...]

        for ($round = 1; $round <= $maxRounds; $round++) {
            $attackerUnits = $computeUnits($state['attacker']);
            $defenderUnits = 0;
            foreach ($state['defenders'] as $df) { $defenderUnits += $computeUnits($df); }
            if ($attackerUnits <= 0 || $defenderUnits <= 0) {
                break;
            }
            $attackerPower = $computePower($state['attacker']);
            $defenderPower = 0;
            foreach ($state['defenders'] as $df) { $defenderPower += $computePower($df); }
            if ($attackerPower <= 0 && $defenderPower <= 0) {
                break;
            }
            $totalPower = max(1, $attackerPower + $defenderPower);
            $attackerKillShare = $attackerPower / $totalPower;
            $defenderKillShare = $defenderPower / $totalPower;
            $attackerKills = min($defenderUnits, max(1, (int) floor($defenderUnits * $eliminationRate * $attackerKillShare)));
            $defenderKills = min($attackerUnits, max(1, (int) floor($attackerUnits * $eliminationRate * $defenderKillShare)));

            // Répartir les kills de l'attaquant entre les flottes défensives proportionnellement au nombre d'unités
            $defenderFleetUnits = [];
            $sumUnits = 0;
            foreach ($state['defenders'] as $idx => $df) {
                $u = $computeUnits($df);
                $defenderFleetUnits[$idx] = $u;
                $sumUnits += $u;
            }
            $allocated = 0;
            $perFleetKills = array_fill(0, count($state['defenders']), 0);
            if ($sumUnits > 0) {
                foreach ($defenderFleetUnits as $idx => $u) {
                    $k = (int) floor($attackerKills * ($u / $sumUnits));
                    $perFleetKills[$idx] = $k;
                    $allocated += $k;
                }
            }
            // Distribuer le reliquat
            $remainingToAssign = $attackerKills - $allocated;
            $i = 0;
            while ($remainingToAssign > 0 && $i < count($perFleetKills)) {
                $perFleetKills[$i]++;
                $remainingToAssign--;
                $i++;
            }

            $roundDetails = [
                'round' => $round,
                'attacker_power' => $attackerPower,
                'defender_power' => $defenderPower,
                'attacker_units' => $attackerUnits,
                'defender_units' => $defenderUnits,
                'attacker_kills' => $attackerKills,
                'defender_kills' => $defenderKills,
                'per_defender_fleet_kills' => [],
            ];

            // Appliquer les kills aux défenseurs
            foreach ($perFleetKills as $idx => $kills) {
                if ($kills <= 0) { $roundDetails['per_defender_fleet_kills'][$idx] = 0; continue; }
                $applyRes = $applyKillsToFleet($state['defenders'][$idx], $kills);
                $defenderTotalLosses += ($kills - $applyRes['unapplied']);
                $roundDetails['per_defender_fleet_kills'][$idx] = $kills - $applyRes['unapplied'];
                $fleetId = $state['defenders'][$idx]['fleet']->id;
                if (!empty($applyRes['ships'])) {
                    $defendersDestroyedShipIds[$fleetId] = array_merge($defendersDestroyedShipIds[$fleetId] ?? [], $applyRes['ships']);
                }
                foreach ($applyRes['stacks'] as $designId => $loss) {
                    $current = $defendersStackLosses[$fleetId][$designId] ?? ['operational' => 0, 'damaged' => 0];
                    $current['operational'] += ($loss['operational'] ?? 0);
                    $current['damaged'] += ($loss['damaged'] ?? 0);
                    $defendersStackLosses[$fleetId][$designId] = $current;
                }
            }

            // Appliquer les kills aux attaquants
            if ($defenderKills > 0) {
                $applyResA = $applyKillsToFleet($state['attacker'], $defenderKills);
                $attackerTotalLosses += ($defenderKills - $applyResA['unapplied']);
                if (!empty($applyResA['ships'])) {
                    $attackerDestroyedShipIds = array_merge($attackerDestroyedShipIds, $applyResA['ships']);
                }
                foreach ($applyResA['stacks'] as $designId => $loss) {
                    $current = $attackerStackLosses[$designId] ?? ['operational' => 0, 'damaged' => 0];
                    $current['operational'] += ($loss['operational'] ?? 0);
                    $current['damaged'] += ($loss['damaged'] ?? 0);
                    $attackerStackLosses[$designId] = $current;
                }
            }

            $rounds[] = $roundDetails;
        }

        // Déterminer le vainqueur
        $finalAttackerUnits = $computeUnits($state['attacker']);
        $finalDefenderUnits = 0;
        foreach ($state['defenders'] as $df) { $finalDefenderUnits += $computeUnits($df); }
        $winner = 'draw';
        if ($finalAttackerUnits > 0 && $finalDefenderUnits <= 0) {
            $winner = 'attacker';
        } elseif ($finalDefenderUnits > 0 && $finalAttackerUnits <= 0) {
            $winner = 'defenders';
        } elseif ($finalAttackerUnits > $finalDefenderUnits) {
            $winner = 'attacker';
        } elseif ($finalDefenderUnits > $finalAttackerUnits) {
            $winner = 'defenders';
        }

        // Règles de mortalité des héros (approximation configurable basée sur moteur d'origine)
        $heroEvents = [];
        $attackerHeroDied = false;
        $defenderHeroDiedFleetIds = [];
        $checkHeroDeathOnFleetDestroyed = (bool) config('oceane.combat.hero_death_check_on_fleet_destroyed', true);
        $heroSurvivalBaseChance = (float) config('oceane.combat.hero_survival_base_chance', 1.0); // en pourcentage
        $heroSurvivalPerSkill = (float) config('oceane.combat.hero_survival_per_combat_skill', 0.0); // en pourcentage par point de skill
        $heroSurvivalMaxChance = (float) config('oceane.combat.hero_survival_max_chance', 95.0);

        if ($checkHeroDeathOnFleetDestroyed) {
            // Attaquant
            $atkHero = $attackerFleet->hero;
            if ($atkHero && $finalAttackerUnits <= 0) {
                $skill = (int) ($atkHero->combat_skill ?? 0);
                $survivalChance = min($heroSurvivalMaxChance, max(0.0, $heroSurvivalBaseChance + $skill * $heroSurvivalPerSkill));
                $roll = random_int(1, 100);
                if ($roll <= (int) round($survivalChance)) {
                    // Echappe de justesse
                    $heroEvents[] = [
                        'type' => 'hero_near_death',
                        'message_key' => 'HEROS_MORT_0000',
                        'fleet_id' => $attackerFleet->id,
                        'commander_id' => $attackerFleet->commander_id,
                        'hero_id' => $atkHero->id,
                        'hero_name' => $atkHero->name,
                        'roll' => $roll,
                        'survival_chance' => $survivalChance,
                    ];
                } else {
                    // Héros meurt
                    $attackerHeroDied = true;
                    $heroEvents[] = [
                        'type' => 'hero_death',
                        'message_key' => 'HEROS_MORT_0001',
                        'fleet_id' => $attackerFleet->id,
                        'commander_id' => $attackerFleet->commander_id,
                        'hero_id' => $atkHero->id,
                        'hero_name' => $atkHero->name,
                        'roll' => $roll,
                        'survival_chance' => $survivalChance,
                    ];
                    // Détacher le héros de la flotte et marquer comme décédé (assignment indicatif)
                    try {
                        $attackerFleet->update(['hero_id' => null]);
                        $atkHero->update(['assignment' => 'deceased']);
                    } catch (\Throwable $e) {
                        Log::warning('Erreur mise à jour décès héros attaquant: ' . $e->getMessage());
                    }
                }
            }
            // Défenseurs
            foreach ($state['defenders'] as $df) {
                /** @var Fleet $fleetObj */
                $fleetObj = $df['fleet'];
                $dfUnits = $computeUnits($df);
                $defHero = $fleetObj->hero;
                if ($defHero && $dfUnits <= 0) {
                    $skill = (int) ($defHero->combat_skill ?? 0);
                    $survivalChance = min($heroSurvivalMaxChance, max(0.0, $heroSurvivalBaseChance + $skill * $heroSurvivalPerSkill));
                    $roll = random_int(1, 100);
                    if ($roll <= (int) round($survivalChance)) {
                        $heroEvents[] = [
                            'type' => 'hero_near_death',
                            'message_key' => 'HEROS_MORT_0000',
                            'fleet_id' => $fleetObj->id,
                            'commander_id' => $fleetObj->commander_id,
                            'hero_id' => $defHero->id,
                            'hero_name' => $defHero->name,
                            'roll' => $roll,
                            'survival_chance' => $survivalChance,
                        ];
                    } else {
                        $defenderHeroDiedFleetIds[$fleetObj->id] = true;
                        $heroEvents[] = [
                            'type' => 'hero_death',
                            'message_key' => 'HEROS_MORT_0001',
                            'fleet_id' => $fleetObj->id,
                            'commander_id' => $fleetObj->commander_id,
                            'hero_id' => $defHero->id,
                            'hero_name' => $defHero->name,
                            'roll' => $roll,
                            'survival_chance' => $survivalChance,
                        ];
                        try {
                            $fleetObj->update(['hero_id' => null]);
                            $defHero->update(['assignment' => 'deceased']);
                        } catch (\Throwable $e) {
                            Log::warning('Erreur mise à jour décès héros défenseur: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        // Appliquer les pertes en base de données
        if (!empty($attackerDestroyedShipIds)) {
            Ship::whereIn('id', $attackerDestroyedShipIds)->update([
                'status' => Ship::STATUS_DESTROYED,
                'hull_points' => 0,
                'shield_points' => 0,
            ]);
        }
        if (!empty($attackerStackLosses)) {
            foreach ($attackerStackLosses as $designId => $loss) {
                $stack = FleetShipStack::where('fleet_id', $attackerFleet->id)->where('ship_design_id', $designId)->lockForUpdate()->first();
                if ($stack) {
                    $newOp = max(0, (int) $stack->count_operational - (int) ($loss['operational'] ?? 0));
                    $newDm = max(0, (int) $stack->count_damaged - (int) ($loss['damaged'] ?? 0));
                    $newDestroyed = (int) $stack->count_destroyed + (int) ($loss['operational'] ?? 0) + (int) ($loss['damaged'] ?? 0);
                    $stack->update([
                        'count_operational' => $newOp,
                        'count_damaged' => $newDm,
                        'count_destroyed' => $newDestroyed,
                    ]);
                }
            }
        }
        foreach ($defendersDestroyedShipIds as $fleetId => $ids) {
            if (!empty($ids)) {
                Ship::whereIn('id', $ids)->update([
                    'status' => Ship::STATUS_DESTROYED,
                    'hull_points' => 0,
                    'shield_points' => 0,
                ]);
            }
        }
        foreach ($defendersStackLosses as $fleetId => $designLosses) {
            foreach ($designLosses as $designId => $loss) {
                $stack = FleetShipStack::where('fleet_id', $fleetId)->where('ship_design_id', $designId)->lockForUpdate()->first();
                if ($stack) {
                    $newOp = max(0, (int) $stack->count_operational - (int) ($loss['operational'] ?? 0));
                    $newDm = max(0, (int) $stack->count_damaged - (int) ($loss['damaged'] ?? 0));
                    $newDestroyed = (int) $stack->count_destroyed + (int) ($loss['operational'] ?? 0) + (int) ($loss['damaged'] ?? 0);
                    $stack->update([
                        'count_operational' => $newOp,
                        'count_damaged' => $newDm,
                        'count_destroyed' => $newDestroyed,
                    ]);
                }
            }
        }

        // Sauvegarder le rapport de combat
        $combatReport->combat_rounds = $rounds;
        $combatReport->results = [
            'winner' => $winner,
            'attacker_losses' => ['units_lost' => $attackerTotalLosses, 'ships_destroyed' => count($attackerDestroyedShipIds), 'stack_losses' => $attackerStackLosses],
            'defenders_losses' => [
                'total_units_lost' => $defenderTotalLosses,
                'per_fleet' => collect($defenderFleets)->map(function (Fleet $f) use ($defendersDestroyedShipIds, $defendersStackLosses) {
                    return [
                        'fleet_id' => $f->id,
                        'fleet_name' => $f->name,
                        'ships_destroyed' => count($defendersDestroyedShipIds[$f->id] ?? []),
                        'stack_losses' => $defendersStackLosses[$f->id] ?? [],
                    ];
                })->values()->toArray(),
            ],
            'hero_events' => $heroEvents,
        ];
        $combatReport->save();

        // Attribuer de l'expérience aux survivants (vaisseaux et héros)
        $roundCount = count($rounds);
        $shipXpPerRound = (int) config('oceane.combat.ship_experience_gain_per_round', 1);
        $heroXpPerRound = (int) config('oceane.combat.hero_experience_gain_per_round', 2);
        $winnerXpBonusFactor = (float) config('oceane.combat.experience_winner_bonus_factor', 1.5);

        // Vaisseaux attaquants survivants
        $attackerSurvivorIds = array_map(function ($s) { return $s['id']; }, $state['attacker']['ships']);
        $attackerShipXpGain = (int) floor($shipXpPerRound * $roundCount * ($winner === 'attacker' ? $winnerXpBonusFactor : 1));
        if ($attackerShipXpGain > 0 && !empty($attackerSurvivorIds)) {
            Ship::whereIn('id', $attackerSurvivorIds)->increment('experience', $attackerShipXpGain);
        }
        // Vaisseaux défenseurs survivants (toutes les flottes)
        foreach ($state['defenders'] as $df) {
            $defSurvivorIds = array_map(function ($s) { return $s['id']; }, $df['ships']);
            $defShipXpGain = (int) floor($shipXpPerRound * $roundCount * ($winner === 'defenders' ? $winnerXpBonusFactor : 1));
            if ($defShipXpGain > 0 && !empty($defSurvivorIds)) {
                Ship::whereIn('id', $defSurvivorIds)->increment('experience', $defShipXpGain);
            }
        }
        // Stacks survivants (XP moyenne par unité) - seulement si la colonne existe
        if (Schema::hasColumn('fleet_ship_stacks', 'experience')) {
            // Attaquant
            $attackerStackXpGain = (int) floor($shipXpPerRound * $roundCount * ($winner === 'attacker' ? $winnerXpBonusFactor : 1));
            if ($attackerStackXpGain > 0 && !empty($state['attacker']['stacks'])) {
                foreach ($state['attacker']['stacks'] as $designId => $s) {
                    if ((int)($s['operational'] + $s['damaged']) > 0) {
                        FleetShipStack::where('fleet_id', $attackerFleet->id)
                            ->where('ship_design_id', $designId)
                            ->increment('experience', $attackerStackXpGain);
                    }
                }
            }
            // Défenseurs
            foreach ($state['defenders'] as $df) {
                /** @var Fleet $fleetObj */
                $fleetObj = $df['fleet'];
                $defStackXpGain = (int) floor($shipXpPerRound * $roundCount * ($winner === 'defenders' ? $winnerXpBonusFactor : 1));
                if ($defStackXpGain > 0 && !empty($df['stacks'])) {
                    foreach ($df['stacks'] as $designId => $s) {
                        if ((int)($s['operational'] + $s['damaged']) > 0) {
                            FleetShipStack::where('fleet_id', $fleetObj->id)
                                ->where('ship_design_id', $designId)
                                ->increment('experience', $defStackXpGain);
                        }
                    }
                }
            }
        }
        // Héros attaquant
        $atkHero = $attackerFleet->hero;
        if ($atkHero && $atkHero->exists && !$attackerHeroDied) {
            $attackerHeroXpGain = (int) floor($heroXpPerRound * $roundCount * ($winner === 'attacker' ? $winnerXpBonusFactor : 1));
            if ($attackerHeroXpGain > 0) {
                $atkHero->increment('experience', $attackerHeroXpGain);
            }
        }
        // Héros défenseurs
        foreach ($defenderFleets as $fleet) {
            $defHero = $fleet->hero;
            if ($defHero && $defHero->exists && !isset($defenderHeroDiedFleetIds[$fleet->id])) {
                $defHeroXpGain = (int) floor($heroXpPerRound * $roundCount * ($winner === 'defenders' ? $winnerXpBonusFactor : 1));
                if ($defHeroXpGain > 0) {
                    $defHero->increment('experience', $defHeroXpGain);
                }
            }
        }

        // Mise à jour des statuts des flottes après le combat
        $this->updateFleetsAfterCombat($attackerFleet, $defenderFleets);
    }
    
    /**
     * Mettre à jour les flottes après un combat
     */
    private function updateFleetsAfterCombat(Fleet $attackerFleet, Collection $defenderFleets): void
    {
        DB::transaction(function () use ($attackerFleet, $defenderFleets) {
            // Attaquant sous verrou
            $attackerLocked = Fleet::where('id', $attackerFleet->id)->lockForUpdate()->first();
            if ($attackerLocked) {
                $attackerHasShips = Ship::where('fleet_id', $attackerLocked->id)
                        ->where('status', '!=', Ship::STATUS_DESTROYED)
                        ->exists()
                    || FleetShipStack::where('fleet_id', $attackerLocked->id)->where(function ($q) {
                        $q->where('count_operational', '>', 0)->orWhere('count_damaged', '>', 0);
                    })->exists();
                if ($attackerHasShips) {
                    $attackerLocked->update(['status' => Fleet::STATUS_DOCKED]);
                    $attackerLocked->increment('experience', config('oceane.combat.experience_gain', 10));
                } else {
                    $attackerLocked->delete();
                }
            }

            // Défenseurs sous verrou
            foreach ($defenderFleets as $fleet) {
                $fleetLocked = Fleet::where('id', $fleet->id)->lockForUpdate()->first();
                if (!$fleetLocked) {
                    continue;
                }
                $fleetHasShips = Ship::where('fleet_id', $fleetLocked->id)
                        ->where('status', '!=', Ship::STATUS_DESTROYED)
                        ->exists()
                    || FleetShipStack::where('fleet_id', $fleetLocked->id)->where(function ($q) {
                        $q->where('count_operational', '>', 0)->orWhere('count_damaged', '>', 0);
                    })->exists();
                if ($fleetHasShips) {
                    $fleetLocked->update(['status' => Fleet::STATUS_DOCKED]);
                    $fleetLocked->increment('experience', config('oceane.combat.experience_gain', 10));
                } else {
                    $fleetLocked->delete();
                }
            }
        }, 3);
    }
    
    /**
     * Supprimer définitivement les flottes planifiées pour suppression à ce tour
     */
    public function processScheduledDeletions(int $currentTurn): void
    {
        // Supprimer toute flotte planifiée pour suppression, sans tenir compte du tour
        $fleets = Fleet::where('scheduled_for_deletion', true)
            ->whereNotNull('scheduled_deletion_turn')
            ->get();

        foreach ($fleets as $fleet) {
            DB::transaction(function () use ($fleet) {
                $locked = Fleet::where('id', $fleet->id)->lockForUpdate()->first();
                if ($locked) {
                    $name = $locked->name;
                    $id = $locked->id;
                    $locked->delete();
                    Log::info("Flotte {$name} (#{$id}) supprimée définitivement (suppression planifiée)");
                }
            }, 3);
        }
    }
}
