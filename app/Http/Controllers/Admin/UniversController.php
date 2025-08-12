<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UniversManager;
use App\Models\Galaxy;
use App\Models\Sector;
use App\Models\StarSystem;
use App\Models\Planet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UniversController extends Controller
{
    protected $universManager;
    
    public function __construct(UniversManager $universManager)
    {
        $this->universManager = $universManager;
        $this->middleware('auth');
        $this->middleware('admin');
    }
    
    /**
     * Afficher le tableau de bord de l'univers
     */
    public function index()
    {
        $stats = $this->getUniversStats();
        
        return view('admin.univers.index', compact('stats'));
    }
    
    /**
     * Obtenir les statistiques de l'univers
     */
    private function getUniversStats()
    {
        return Cache::remember('univers_stats', 60, function () {
            return [
                'galaxy_count' => Galaxy::count(),
                'sector_count' => Sector::count(),
                'star_system_count' => StarSystem::count(),
                'planet_count' => Planet::count(),
                'habitable_planet_count' => Planet::whereIn('type', config('oceane.planet.habitable_types', [0, 1, 2, 3, 4]))->count(),
                'colonized_planet_count' => Planet::where('is_colonized', true)->count(),
            ];
        });
    }
    
    /**
     * Afficher le formulaire d'initialisation de l'univers
     */
    public function showInitializeForm()
    {
        $hasUnivers = Galaxy::count() > 0;
        return view('admin.univers.initialize', compact('hasUnivers'));
    }
    
    /**
     * Initialiser un nouvel univers
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'galaxy_count' => 'required|integer|min:1|max:10',
            'confirm_reset' => 'required|boolean',
        ]);
        
        if (Galaxy::count() > 0 && !$request->confirm_reset) {
            return back()->withErrors(['confirm_reset' => 'Vous devez confirmer la réinitialisation de l\'univers']);
        }
        
        // Réinitialiser l'univers si nécessaire
        if (Galaxy::count() > 0) {
            $this->universManager->resetUnivers();
        }
        
        // Initialiser un nouvel univers
        $this->universManager->initializeUnivers($request->galaxy_count);
        
        // Générer des systèmes stellaires dans l'univers
        $this->universManager->generateStarSystems();
        
        // Créer les passages galactiques
        if ($request->galaxy_count > 1) {
            $this->universManager->createGalacticPortals();
        }
        
        // Vider le cache des statistiques
        Cache::forget('univers_stats');
        
        return redirect()->route('admin.univers.index')
            ->with('success', 'Univers initialisé avec ' . $request->galaxy_count . ' galaxies');
    }
    
    /**
     * Afficher la liste des galaxies
     */
    public function galaxies()
    {
        $galaxies = Galaxy::withCount(['sectors', 'starSystems', 'planets'])->get();
        return view('admin.univers.galaxies.index', compact('galaxies'));
    }
    
    /**
     * Afficher les détails d'une galaxie
     */
    public function showGalaxy($id)
    {
        $galaxy = Galaxy::with(['sectors' => function($query) {
            $query->withCount(['starSystems', 'planets']);
        }])->findOrFail($id);
        
        return view('admin.univers.galaxies.show', compact('galaxy'));
    }
    
    /**
     * Afficher la liste des secteurs
     */
    public function sectors(Request $request)
    {
        $query = Sector::query();
        
        if ($request->has('galaxy_id')) {
            $query->where('galaxy_id', $request->galaxy_id);
        }
        
        $sectors = $query->withCount(['starSystems', 'planets'])
                        ->orderBy('galaxy_id')
                        ->orderBy('sector_number')
                        ->paginate(20);
                        
        $galaxies = Galaxy::pluck('name', 'id');
        
        return view('admin.univers.sectors.index', compact('sectors', 'galaxies'));
    }
    
    /**
     * Afficher les détails d'un secteur
     */
    public function showSector($id)
    {
        $sector = Sector::with(['starSystems' => function($query) {
            $query->withCount('planets');
        }])->findOrFail($id);
        
        return view('admin.univers.sectors.show', compact('sector'));
    }
    
    /**
     * Afficher la liste des systèmes stellaires
     */
    public function starSystems(Request $request)
    {
        $query = StarSystem::with(['sector.galaxy', 'commander']);
        
        if ($request->has('sector_id')) {
            $query->where('sector_id', $request->sector_id);
        }
        
        if ($request->has('is_controlled')) {
            $query->where('is_controlled', $request->is_controlled);
        }
        
        $starSystems = $query->withCount('planets')
                            ->orderBy('name')
                            ->paginate(20);
                            
        $sectors = Sector::orderBy('galaxy_id')->orderBy('sector_number')
                         ->get()
                         ->map(function($sector) {
                            return [
                                'id' => $sector->id,
                                'name' => "G{$sector->galaxy_id}-S{$sector->sector_number} {$sector->name}"
                            ];
                         })
                         ->pluck('name', 'id');
        
        return view('admin.univers.star_systems.index', compact('starSystems', 'sectors'));
    }
    
    /**
     * Afficher les détails d'un système stellaire
     */
    public function showStarSystem($id)
    {
        $starSystem = StarSystem::with(['sector.galaxy', 'commander', 'planets'])->findOrFail($id);
        
        return view('admin.univers.star_systems.show', compact('starSystem'));
    }
    
    /**
     * Afficher la liste des planètes
     */
    public function planets(Request $request)
    {
        $query = Planet::with(['starSystem.sector.galaxy', 'commander']);
        
        if ($request->has('star_system_id')) {
            $query->where('star_system_id', $request->star_system_id);
        }
        
        if ($request->has('is_colonized')) {
            $query->where('is_colonized', $request->is_colonized);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        $planets = $query->orderBy('name')
                        ->paginate(20);
                        
        $starSystems = StarSystem::orderBy('name')
                                ->pluck('name', 'id');
                                
        $planetTypes = config('oceane.planet.types', []);
        
        return view('admin.univers.planets.index', compact('planets', 'starSystems', 'planetTypes'));
    }
    
    /**
     * Afficher les détails d'une planète
     */
    public function showPlanet($id)
    {
        $planet = Planet::with(['starSystem.sector.galaxy', 'commander'])->findOrFail($id);
        
        return view('admin.univers.planets.show', compact('planet'));
    }
    
    /**
     * Générer les images des galaxies
     */
    public function generateImages()
    {
        $this->universManager->generateGalaxyImages();
        
        return back()->with('success', 'Images des galaxies générées avec succès');
    }
    
    /**
     * Afficher la carte de l'univers
     */
    public function map(Request $request)
    {
        $galaxyId = $request->input('galaxy_id', 1);
        
        $galaxy = Galaxy::findOrFail($galaxyId);
        $sectors = Sector::where('galaxy_id', $galaxyId)->get();
        $starSystems = StarSystem::whereHas('sector', function($query) use ($galaxyId) {
            $query->where('galaxy_id', $galaxyId);
        })->with('sector', 'commander')->get();
        
        return view('admin.univers.map', compact('galaxy', 'sectors', 'starSystems'));
    }
}
