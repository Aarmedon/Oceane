<?php

namespace App\Services;

use App\Models\Technology;
use App\Models\Commander;
use App\Models\Planet;
use App\Models\GameState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class TechnologyManager
{
    /**
     * Vérifier si un commandant peut rechercher une technologie
     */
    public function canResearch(Commander $commander, Technology $technology): bool
    {
        // Vérifier si le commandant a déjà cette technologie au niveau maximum
        $commanderTech = $commander->technologies()
                                  ->where('technology_id', $technology->id)
                                  ->first();
        
        if ($commanderTech && $commanderTech->pivot->level >= $technology->max_level) {
            return false;
        }
        
        // Vérifier les prérequis technologiques
        if ($technology->prerequisite_technology_id) {
            $prerequisite = $commander->technologies()
                                    ->where('technology_id', $technology->prerequisite_technology_id)
                                    ->first();
            
            if (!$prerequisite || $prerequisite->pivot->level < $technology->prerequisite_level) {
                return false;
            }
        }
        
        // Vérifier si le commandant a les ressources nécessaires
        $cost = $this->calculateResearchCost($technology, $commanderTech ? $commanderTech->pivot->level + 1 : 1);
        if ($commander->credits < $cost) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculer le coût de recherche d'une technologie à un niveau donné
     */
    public function calculateResearchCost(Technology $technology, int $targetLevel): float
    {
        $baseCost = $technology->base_research_cost;
        $costMultiplier = $technology->level_multiplier;
        
        // La formule de coût augmente exponentiellement avec le niveau
        return $baseCost * pow($costMultiplier, $targetLevel - 1);
    }
    
    /**
     * Démarrer la recherche d'une technologie pour un commandant
     */
    public function startResearch(Commander $commander, Technology $technology): array
    {
        // Vérifier si la recherche est possible
        if (!$this->canResearch($commander, $technology)) {
            throw new \Exception("Impossible de rechercher cette technologie.");
        }
        
        // Déterminer le niveau cible
        $currentLevel = 0;
        $currentResearch = $commander->technologies()
                                    ->where('technology_id', $technology->id)
                                    ->first();
        
        if ($currentResearch) {
            $currentLevel = $currentResearch->pivot->level;
        }
        
        $targetLevel = $currentLevel + 1;
        
        // Calculer le coût et le temps de recherche
        $cost = $this->calculateResearchCost($technology, $targetLevel);
        $researchTime = $this->calculateResearchTime($technology, $targetLevel, $commander);
        
        // Déduire les crédits du commandant
        $commander->decrement('credits', $cost);
        
        // Lier la technologie au commandant ou mettre à jour la relation
        $currentTurn = (int) (GameState::query()->value('current_turn') ?? 1);
        $researchData = [
            'research_progress' => 0,
            'research_total' => 100, // Progression en pourcentage
            'is_researching' => true,
            'research_completion_turn' => $currentTurn + $researchTime
        ];
        
        if ($currentResearch) {
            $commander->technologies()->updateExistingPivot($technology->id, $researchData);
        } else {
            $commander->technologies()->attach($technology->id, array_merge([
                'level' => 0,
            ], $researchData));
        }
        
        Log::info("Commandant {$commander->name} a commencé la recherche de {$technology->name} niveau {$targetLevel}, complétion au tour {$researchData['research_completion_turn']}");
        
        return [
            'technology' => $technology->name,
            'target_level' => $targetLevel,
            'cost' => $cost,
            'research_time' => $researchTime,
            'completion_turn' => $researchData['research_completion_turn']
        ];
    }
    
    /**
     * Calculer le temps de recherche pour une technologie
     */
    private function calculateResearchTime(Technology $technology, int $targetLevel, Commander $commander): int
    {
        $baseTime = config('oceane.technology.base_research_time', 3); // tours
        
        // Facteur de difficulté basé sur la catégorie et le niveau
        $difficultyFactor = 1;
        switch ($technology->category) {
            case 'military':
                $difficultyFactor = 1.2;
                break;
            case 'economic':
                $difficultyFactor = 0.8;
                break;
            case 'advanced':
                $difficultyFactor = 1.5;
                break;
        }
        
        // Le temps augmente avec le niveau
        $levelFactor = pow(1.2, $targetLevel - 1);
        
        // Réduction du temps basée sur les planètes de recherche
        $researchPlanets = $commander->planets()
                                    ->where('primary_activity', 'research')
                                    ->get();
        
        $researchBonus = 1 - (min($researchPlanets->count(), 5) * 0.05);
        
        // Réduction du temps basée sur la race du commandant
        $raceBonus = $commander->race->research_bonus > 0 
                    ? 1 - ($commander->race->research_bonus / 100) 
                    : 1;
        
        // Calcul final
        $researchTime = ceil($baseTime * $difficultyFactor * $levelFactor * $researchBonus * $raceBonus);
        
        // Temps minimum de recherche
        return max(1, $researchTime);
    }
    
    /**
     * Traiter toutes les recherches en cours pour un tour donné
     */
    public function processTechnologyResearch(int $currentTurn): void
    {
        // Récupérer toutes les recherches qui doivent être terminées à ce tour
        $commanderTechs = \DB::table('commander_technologies')
                           ->where('is_researching', true)
                           ->where('research_completion_turn', $currentTurn)
                           ->get();
        
        foreach ($commanderTechs as $research) {
            $this->completeResearch($research->commander_id, $research->technology_id);
        }
        
        Log::info("{$commanderTechs->count()} recherches technologiques complétées au tour {$currentTurn}");
    }
    
    /**
     * Compléter une recherche technologique
     */
    private function completeResearch(int $commanderId, int $technologyId): void
    {
        $commander = Commander::findOrFail($commanderId);
        $technology = Technology::findOrFail($technologyId);
        
        $commanderTech = $commander->technologies()
                                  ->where('technology_id', $technologyId)
                                  ->first();
        
        if (!$commanderTech) {
            Log::error("Technologie non trouvée pour le commandant {$commanderId}");
            return;
        }
        
        $newLevel = $commanderTech->pivot->level + 1;
        
        // Mettre à jour le niveau de la technologie
        $commander->technologies()->updateExistingPivot($technologyId, [
            'level' => $newLevel,
            'is_researching' => false,
            'research_progress' => 0,
            'research_total' => 0,
            'research_completion_turn' => null
        ]);
        
        Log::info("Commandant {$commander->name} a terminé la recherche de {$technology->name} niveau {$newLevel}");
        
        // Déclencher les effets de la nouvelle technologie
        $this->applyTechnologyEffects($commander, $technology, $newLevel);
    }
    
    /**
     * Appliquer les effets d'une technologie nouvellement acquise
     */
    private function applyTechnologyEffects(Commander $commander, Technology $technology, int $level): void
    {
        // Effets spécifiques selon la catégorie et le type de technologie
        switch ($technology->category) {
            case 'military':
                $this->applyMilitaryTechnologyEffects($commander, $technology, $level);
                break;
                
            case 'economic':
                $this->applyEconomicTechnologyEffects($commander, $technology, $level);
                break;
                
            case 'infrastructure':
                $this->applyInfrastructureTechnologyEffects($commander, $technology, $level);
                break;
                
            case 'propulsion':
                $this->applyPropulsionTechnologyEffects($commander, $technology, $level);
                break;
                
            case 'advanced':
                $this->applyAdvancedTechnologyEffects($commander, $technology, $level);
                break;
        }
        
        // Vérifier si de nouvelles technologies sont déverrouillées
        $this->checkUnlockedTechnologies($commander);
    }
    
    /**
     * Appliquer les effets des technologies militaires
     */
    private function applyMilitaryTechnologyEffects(Commander $commander, Technology $technology, int $level): void
    {
        // TODO: Implémenter les effets spécifiques
    }
    
    /**
     * Appliquer les effets des technologies économiques
     */
    private function applyEconomicTechnologyEffects(Commander $commander, Technology $technology, int $level): void
    {
        // TODO: Implémenter les effets spécifiques
    }
    
    /**
     * Appliquer les effets des technologies d'infrastructure
     */
    private function applyInfrastructureTechnologyEffects(Commander $commander, Technology $technology, int $level): void
    {
        // TODO: Implémenter les effets spécifiques
    }
    
    /**
     * Appliquer les effets des technologies de propulsion
     */
    private function applyPropulsionTechnologyEffects(Commander $commander, Technology $technology, int $level): void
    {
        // TODO: Implémenter les effets spécifiques
    }
    
    /**
     * Appliquer les effets des technologies avancées
     */
    private function applyAdvancedTechnologyEffects(Commander $commander, Technology $technology, int $level): void
    {
        // TODO: Implémenter les effets spécifiques
    }
    
    /**
     * Vérifier si de nouvelles technologies sont déverrouillées
     */
    private function checkUnlockedTechnologies(Commander $commander): void
    {
        // TODO: Implémenter la vérification des nouvelles technologies déverrouillées
    }
    
    /**
     * Obtenir toutes les technologies disponibles pour un commandant
     */
    public function getAvailableTechnologies(Commander $commander): Collection
    {
        $commanderTechs = $commander->technologies->pluck('pivot.level', 'id')->toArray();
        
        return Technology::all()->filter(function ($technology) use ($commanderTechs) {
            // Si la technologie a déjà atteint le niveau max
            if (isset($commanderTechs[$technology->id]) && 
                $commanderTechs[$technology->id] >= $technology->max_level) {
                return false;
            }
            
            // Vérifier les prérequis
            if ($technology->prerequisite_technology_id) {
                $requiredLevel = $technology->prerequisite_level;
                $actualLevel = $commanderTechs[$technology->prerequisite_technology_id] ?? 0;
                
                if ($actualLevel < $requiredLevel) {
                    return false;
                }
            }
            
            return true;
        });
    }
}
