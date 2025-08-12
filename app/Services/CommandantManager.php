<?php

namespace App\Services;

use App\Models\Commander;
use App\Models\Race;
use App\Models\User;
use App\Models\StarSystem;
use App\Models\Planet;
use App\Models\Hero;
use App\Models\GameState;
use Illuminate\Support\Facades\Log;

class CommandantManager
{
    /**
     * Créer un nouveau commandant pour un utilisateur
     */
    public function createCommander(User $user, string $name, int $raceId, ?string $description = null): Commander
    {
        // Vérifier si l'utilisateur a déjà un commandant
        if ($user->commanders()->count() > 0) {
            throw new \Exception("Cet utilisateur possède déjà un commandant.");
        }
        
        // Vérifier si le nom du commandant est unique
        if (Commander::where('name', $name)->exists()) {
            throw new \Exception("Un commandant avec ce nom existe déjà.");
        }
        
        // Vérifier si l'ID de race est valide (entre 1 et 4)
        if ($raceId < 1 || $raceId > 4) {
            throw new \Exception("Race invalide.");
        }
        
        // Obtenir le tour actuel du jeu depuis l'état persistant
        $currentTurn = (int) (GameState::query()->value('current_turn') ?? 1);
        
        // Créer le commandant
        $commander = Commander::create([
            'user_id' => $user->id,
            'name' => $name,
            'race_id' => $raceId,
            'credits' => config('oceane.commander.starting_credits', 1000),
            'reputation' => config('oceane.commander.starting_reputation', 0),
            'description' => $description,
            'created_turn' => $currentTurn
        ]);
        
        Log::info("Commandant {$name} créé pour l'utilisateur {$user->id} avec la race ID {$raceId}");
        
        // Créer un héros initial pour le commandant
        $this->createInitialHero($commander);
        
        return $commander;
    }
    
    /**
     * Créer un héros initial pour un commandant
     */
    private function createInitialHero(Commander $commander): Hero
    {
        // Utiliser directement l'ID de race du commandant
        $raceId = $commander->race_id;
        
        $hero = Hero::create([
            'commander_id' => $commander->id,
            'name' => $this->generateHeroName($raceId),
            'type' => 'hero', // hero ou gouverneur
            'combat_skill' => rand(1, 3),
            'diplomacy_skill' => rand(1, 3),
            'management_skill' => rand(1, 3),
            'experience' => 0,
            'level' => 1,
            'assignment' => null, // null = réserve
            'maintenance_cost' => config('oceane.hero.base_maintenance', 50)
        ]);
        
        Log::info("Héros initial {$hero->name} créé pour le commandant {$commander->name}");
        
        return $hero;
    }
    
    /**
     * Générer un nom de héros basé sur la race
     */
    private function generateHeroName(int $raceId): string
    {
        $raceNames = [
            1 => ['Thorn', 'Axel', 'Marcus', 'Victor', 'Helena', 'Sophia', 'Anya', 'Katerina'],
            2 => ['Zor\'Gul', 'Kra\'Thos', 'Grum\'Nak', 'Thrak\'Tor', 'Zar\'Kia', 'Gra\'Mak', 'Vex\'Na', 'Kri\'Tala'],
            3 => ['Elyon', 'Thaelis', 'Finarel', 'Calathir', 'Seraphine', 'Elanil', 'Miralei', 'Thaeloria'],
            4 => ['X\'drul', 'K\'zith', 'N\'voth', 'Z\'kran', 'Sh\'ress', 'T\'zara', 'L\'mira', 'N\'yara'],
            5 => ['Gorim', 'Thorin', 'Balin', 'Durin', 'Hilda', 'Dagmar', 'Brunhild', 'Freya'],
            // Ajouter d'autres races selon besoin
        ];
        
        $names = $raceNames[$raceId] ?? ['Alpha', 'Beta', 'Gamma', 'Delta', 'Omega'];
        
        return $names[array_rand($names)];
    }
    
    /**
     * Attribuer un système initial à un commandant
     */
    public function assignStartingSystem(Commander $commander): StarSystem
    {
        // Obtenir un système non contrôlé aléatoirement
        $startingSystem = StarSystem::where('is_controlled', false)
            ->inRandomOrder()
            ->first();
            
        if (!$startingSystem) {
            throw new \Exception("Aucun système disponible pour l'attribution initiale.");
        }
        
        // Attribuer le système au commandant
        $startingSystem->update([
            'is_controlled' => true,
            'commander_id' => $commander->id,
            'tax_rate' => config('oceane.system.default_tax_rate', 2)
        ]);
        
        // Définir ce système comme capitale du commandant
        $commander->update([
            'capital_system_id' => $startingSystem->id
        ]);
        
        // Trouver une planète habitable dans ce système
        $habitablePlanet = Planet::where('star_system_id', $startingSystem->id)
            ->whereIn('type', config('oceane.planet.habitable_types', [0, 1, 2, 3, 4]))
            ->inRandomOrder()
            ->first();
        
        if ($habitablePlanet) {
            // Coloniser la planète
            $habitablePlanet->update([
                'is_colonized' => true,
                'commander_id' => $commander->id
            ]);
            
            // Créer la population initiale
            $this->createInitialPopulation($habitablePlanet, $commander);
        }
        
        Log::info("Système {$startingSystem->name} attribué au commandant {$commander->name}");
        
        return $startingSystem;
    }
    
    /**
     * Créer une population initiale pour une planète
     */
    private function createInitialPopulation($planet, $commander)
    {
        // TODO: Implémenter la création de population
    }
    
    /**
     * Calculer et mettre à jour le budget d'un commandant pour un tour
     */
    public function updateBudget(Commander $commander, int $turn): array
    {
        $budget = [
            'previous_credits' => $commander->credits,
            'system_income' => 0,
            'alliance_income' => 0,
            'trade_income' => 0,
            'royalties_income' => 0,
            'other_income' => 0,
            'total_income' => 0,
            
            'fleet_maintenance' => 0,
            'system_maintenance' => 0,
            'hero_maintenance' => 0,
            'technology_maintenance' => 0,
            'other_expenses' => 0,
            'total_expenses' => 0,
            
            'new_balance' => 0
        ];
        
        // Calculer les revenus des systèmes
        $systems = $commander->starSystems;
        foreach ($systems as $system) {
            $systemIncome = $this->calculateSystemIncome($system);
            $budget['system_income'] += $systemIncome;
        }
        
        // Calculer les revenus d'alliance
        $allianceIncome = $this->calculateAllianceIncome($commander);
        $budget['alliance_income'] = $allianceIncome;
        
        // Calculer les dépenses d'entretien des flottes
        $fleetMaintenance = $this->calculateFleetMaintenance($commander);
        $budget['fleet_maintenance'] = $fleetMaintenance;
        
        // Calculer les dépenses d'entretien des systèmes
        $systemMaintenance = $this->calculateSystemMaintenance($commander);
        $budget['system_maintenance'] = $systemMaintenance;
        
        // Calculer les dépenses d'entretien des héros
        $heroMaintenance = $this->calculateHeroMaintenance($commander);
        $budget['hero_maintenance'] = $heroMaintenance;
        
        // Calculer les totaux
        $budget['total_income'] = $budget['system_income'] + $budget['alliance_income'] + $budget['trade_income'] + $budget['royalties_income'] + $budget['other_income'];
        $budget['total_expenses'] = $budget['fleet_maintenance'] + $budget['system_maintenance'] + $budget['hero_maintenance'] + $budget['technology_maintenance'] + $budget['other_expenses'];
        $budget['new_balance'] = $budget['previous_credits'] + $budget['total_income'] - $budget['total_expenses'];
        
        // Mettre à jour les crédits du commandant
        $commander->update([
            'credits' => max(0, $budget['new_balance'])
        ]);
        
        return $budget;
    }
    
    /**
     * Calculer les revenus d'un système
     */
    private function calculateSystemIncome(StarSystem $system): int
    {
        // Formule de base pour les revenus: taxe * (nombre de planètes colonisées + 1)
        $colonizedPlanets = $system->planets->where('is_colonized', true)->count();
        $income = $system->tax_rate * 100 * ($colonizedPlanets + 1);
        
        return $income;
    }
    
    /**
     * Calculer les revenus d'alliance
     */
    private function calculateAllianceIncome(Commander $commander): int
    {
        $income = 0;
        
        // Si le commandant est membre d'une alliance anarchique, il reçoit un revenu fixe
        $anarchicAlliance = $commander->alliances()->where('type', 2)->first();
        if ($anarchicAlliance) {
            $income += config('oceane.alliance.anarchic_income', 100);
        }
        
        return $income;
    }
    
    /**
     * Calculer les dépenses d'entretien des flottes
     */
    private function calculateFleetMaintenance(Commander $commander): int
    {
        $maintenance = 0;
        
        $fleets = $commander->fleets;
        foreach ($fleets as $fleet) {
            $maintenance += $fleet->maintenance_cost;
        }
        
        return $maintenance;
    }
    
    /**
     * Calculer les dépenses d'entretien des systèmes
     */
    private function calculateSystemMaintenance(Commander $commander): int
    {
        // Pour simplifier, on calcule un coût fixe par système possédé
        $systemCount = $commander->starSystems->count();
        $maintenance = $systemCount * config('oceane.system.maintenance_cost', 50);
        
        return $maintenance;
    }
    
    /**
     * Calculer les dépenses d'entretien des héros
     */
    private function calculateHeroMaintenance(Commander $commander): int
    {
        $maintenance = 0;
        
        $heroes = $commander->heroes;
        foreach ($heroes as $hero) {
            $maintenance += $hero->maintenance_cost;
        }
        
        return $maintenance;
    }
}
