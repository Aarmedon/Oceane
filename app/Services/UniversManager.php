<?php

namespace App\Services;

use App\Models\Galaxy;
use App\Models\Sector;
use App\Models\StarSystem;
use App\Models\Planet;
use Illuminate\Support\Facades\Log;

class UniversManager
{
    /**
     * Initialiser un nouvel univers avec le nombre spécifié de galaxies
     */
    public function initializeUnivers(int $galaxyCount = 2): void
    {
        Log::info("Initialisation de l'univers avec {$galaxyCount} galaxies");
        
        // Vérifier si l'univers existe déjà
        if (Galaxy::count() > 0) {
            throw new \Exception("L'univers existe déjà. Utilisez resetUnivers() pour le réinitialiser.");
        }
        
        // Créer les galaxies
        for ($i = 1; $i <= $galaxyCount; $i++) {
            $galaxy = Galaxy::create([
                'name' => "Galaxie {$i}",
                'size_x' => config('oceane.galaxy.size_x', 100),
                'size_y' => config('oceane.galaxy.size_y', 100),
                'image_path' => null
            ]);
            
            Log::info("Galaxie {$i} créée avec l'ID {$galaxy->id}");
            
            // Créer les secteurs pour cette galaxie
            $this->createSectors($galaxy);
        }
    }
    
    /**
     * Créer les secteurs pour une galaxie
     */
    private function createSectors(Galaxy $galaxy): void
    {
        $sectorCount = config('oceane.galaxy.sector_count', 25);
        $sizeX = $galaxy->size_x;
        $sizeY = $galaxy->size_y;
        
        $sectorSizeX = $sizeX / sqrt($sectorCount);
        $sectorSizeY = $sizeY / sqrt($sectorCount);
        
        $sectors = [];
        
        for ($i = 0; $i < sqrt($sectorCount); $i++) {
            for ($j = 0; $j < sqrt($sectorCount); $j++) {
                $sectorNumber = $i * sqrt($sectorCount) + $j + 1;
                
                $sectors[] = [
                    'galaxy_id' => $galaxy->id,
                    'sector_number' => $sectorNumber,
                    'name' => "Secteur {$sectorNumber}",
                    'position_x_start' => $j * $sectorSizeX + 1,
                    'position_y_start' => $i * $sectorSizeY + 1,
                    'position_x_end' => ($j + 1) * $sectorSizeX,
                    'position_y_end' => ($i + 1) * $sectorSizeY,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        
        Sector::insert($sectors);
        Log::info("{$sectorCount} secteurs créés pour la galaxie {$galaxy->id}");
    }
    
    /**
     * Générer des systèmes stellaires pour tous les secteurs
     */
    public function generateStarSystems(): void
    {
        $sectors = Sector::all();
        
        foreach ($sectors as $sector) {
            $this->generateStarSystemsForSector($sector);
        }
        
        Log::info("Génération des systèmes stellaires terminée");
    }
    
    /**
     * Générer des systèmes stellaires pour un secteur spécifique
     */
    private function generateStarSystemsForSector(Sector $sector): void
    {
        $minSystems = config('oceane.sector.min_star_systems', 10);
        $maxSystems = config('oceane.sector.max_star_systems', 20);
        
        $systemCount = rand($minSystems, $maxSystems);
        
        $systems = [];
        $usedPositions = [];
        
        for ($i = 0; $i < $systemCount; $i++) {
            // Générer une position unique pour chaque système
            do {
                $posX = rand($sector->position_x_start, $sector->position_x_end);
                $posY = rand($sector->position_y_start, $sector->position_y_end);
                $posKey = "{$posX}-{$posY}";
            } while (isset($usedPositions[$posKey]));
            
            $usedPositions[$posKey] = true;
            
            $systems[] = [
                'sector_id' => $sector->id,
                'name' => $this->generateStarSystemName(),
                'position_x' => $posX,
                'position_y' => $posY,
                'star_type' => rand(0, config('oceane.star_types_count', 5) - 1),
                'is_controlled' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        StarSystem::insert($systems);
        Log::info("{$systemCount} systèmes stellaires générés pour le secteur {$sector->id}");
        
        // Générer des planètes pour chaque système
        $starSystems = StarSystem::where('sector_id', $sector->id)->get();
        foreach ($starSystems as $starSystem) {
            $this->generatePlanetsForSystem($starSystem);
        }
    }
    
    /**
     * Générer des planètes pour un système stellaire
     */
    private function generatePlanetsForSystem(StarSystem $starSystem): void
    {
        $minPlanets = config('oceane.system.min_planets', 0);
        $maxPlanets = config('oceane.system.max_planets', 8);
        
        $planetCount = rand($minPlanets, $maxPlanets);
        
        $planets = [];
        
        for ($i = 1; $i <= $planetCount; $i++) {
            $planets[] = [
                'star_system_id' => $starSystem->id,
                'name' => $starSystem->name . " " . $this->getPlanetaryDesignation($i),
                'position_in_system' => $i,
                'size' => rand(1, config('oceane.planet.max_size', 5)),
                'type' => rand(0, config('oceane.planet.max_type', 19)),
                'mineral_resources' => rand(0, config('oceane.planet.max_resources', 5)),
                'radiation_level' => rand(
                    config('oceane.planet.min_radiation', 0),
                    config('oceane.planet.max_radiation', 200)
                ),
                'temperature' => rand(
                    config('oceane.planet.min_temperature', -150),
                    config('oceane.planet.max_temperature', 200)
                ),
                'gravity' => rand(
                    config('oceane.planet.min_gravity', 0),
                    config('oceane.planet.max_gravity', 100)
                ),
                'atmosphere_type' => rand(0, config('oceane.planet.atmosphere_types', 10) - 1),
                'is_colonized' => false,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
        
        if (!empty($planets)) {
            Planet::insert($planets);
            Log::info("{$planetCount} planètes générées pour le système {$starSystem->id}");
        }
    }
    
    /**
     * Générer un nom pour un système stellaire
     */
    private function generateStarSystemName(): string
    {
        $prefixes = ['Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa', 
                    'Lambda', 'Mu', 'Nu', 'Xi', 'Omicron', 'Pi', 'Rho', 'Sigma', 'Tau', 'Upsilon', 'Phi', 
                    'Chi', 'Psi', 'Omega'];
        
        $suffixes = ['Prime', 'Secundus', 'Tertius', 'Quartus', 'Quintus', 'Sextus', 'Septimus', 'Octavus', 'Nonus', 'Decimus'];
        
        $prefix = $prefixes[array_rand($prefixes)];
        $number = rand(1, 999);
        
        if (rand(0, 1)) {
            $suffix = $suffixes[array_rand($suffixes)];
            return "{$prefix} {$number} {$suffix}";
        }
        
        return "{$prefix} {$number}";
    }
    
    /**
     * Obtenir la désignation d'une planète (I, II, III, etc.)
     */
    private function getPlanetaryDesignation(int $position): string
    {
        $designations = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 
                        'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX'];
        
        return $designations[$position - 1] ?? "P-{$position}";
    }
    
    /**
     * Créer les passages galactiques entre les galaxies
     */
    public function createGalacticPortals(): void
    {
        // TODO: Implémenter la création des portes galactiques
    }
    
    /**
     * Réinitialiser complètement l'univers
     */
    public function resetUnivers(): void
    {
        // Supprime toutes les données de l'univers
        Planet::truncate();
        StarSystem::truncate();
        Sector::truncate();
        Galaxy::truncate();
        
        Log::info("L'univers a été réinitialisé");
    }
    
    /**
     * Générer les images des galaxies et secteurs
     */
    public function generateGalaxyImages(): void
    {
        // TODO: Implémenter la génération d'images
    }
}
