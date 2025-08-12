@extends('layouts.app')

@section('title', $planet->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/planet.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('game.galaxy_map', ['galaxy_id' => $planet->starSystem->sector->galaxy_id]) }}">
                            {{ $planet->starSystem->sector->galaxy->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('game.star_system', $planet->star_system_id) }}">
                            {{ $planet->starSystem->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $planet->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="fas fa-globe"></i> 
                        {{ $planet->name }}
                        @if($planet->commander_id === $commander->id)
                            <span class="badge bg-success">Votre planète</span>
                        @elseif($planet->commander_id)
                            <span class="badge bg-danger">Planète ennemie</span>
                        @else
                            <span class="badge bg-warning text-dark">Inoccupée</span>
                        @endif
                    </h4>
                    
                    @if(!$planet->commander_id && $isOwner)
                        <form action="{{ route('game.colonize_planet', $planet->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-flag"></i> Coloniser
                            </button>
                        </form>
                    @endif
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Visualisation de la planète -->
                        <div class="col-md-5">
                            <div class="planet-view">
                                <div class="planet-sphere" id="planetSphere"></div>
                            </div>
                        </div>
                        
                        <!-- Informations de la planète -->
                        <div class="col-md-7">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Caractéristiques</h5>
                                    <ul class="list-group list-group-flush bg-dark">
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Type:</span>
                                            <strong>{{ config('oceane.planet.types.' . $planet->planet_type) }}</strong>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Taille:</span>
                                            <strong>{{ $planet->size }}/{{ config('oceane.planet.max_size') }}</strong>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Température:</span>
                                            <strong>{{ $planet->temperature }}°C</strong>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Gravité:</span>
                                            <strong>{{ $planet->gravity }} g</strong>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Radiation:</span>
                                            <strong>{{ $planet->radiation }} mSv</strong>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Atmosphère:</span>
                                            <strong>{{ $planet->atmosphere_type ? 'Type ' . $planet->atmosphere_type : 'Aucune' }}</strong>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                            <span>Habitabilité:</span>
                                            <strong>
                                                @if(in_array($planet->planet_type, config('oceane.planet.habitable_types')))
                                                    Habitable
                                                @else
                                                    Inhabitable
                                                @endif
                                            </strong>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="col-md-6">
                                    <h5>Ressources</h5>
                                    <ul class="list-group list-group-flush bg-dark">
                                        <li class="list-group-item bg-dark text-light border-secondary">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Minéraux:</span>
                                                <strong>{{ $planet->mineral_richness }}/{{ config('oceane.planet.max_resources') }}</strong>
                                            </div>
                                            <div class="resource-bar">
                                                <div class="resource-level bg-primary" style="width: {{ ($planet->mineral_richness / config('oceane.planet.max_resources')) * 100 }}%"></div>
                                            </div>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Énergie:</span>
                                                <strong>{{ $planet->energy_richness }}/{{ config('oceane.planet.max_resources') }}</strong>
                                            </div>
                                            <div class="resource-bar">
                                                <div class="resource-level bg-warning" style="width: {{ ($planet->energy_richness / config('oceane.planet.max_resources')) * 100 }}%"></div>
                                            </div>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Organique:</span>
                                                <strong>{{ $planet->organic_richness }}/{{ config('oceane.planet.max_resources') }}</strong>
                                            </div>
                                            <div class="resource-bar">
                                                <div class="resource-level bg-success" style="width: {{ ($planet->organic_richness / config('oceane.planet.max_resources')) * 100 }}%"></div>
                                            </div>
                                        </li>
                                        <li class="list-group-item bg-dark text-light border-secondary">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Eau:</span>
                                                <strong>{{ $planet->water_richness }}/{{ config('oceane.planet.max_resources') }}</strong>
                                            </div>
                                            <div class="resource-bar">
                                                <div class="resource-level bg-info" style="width: {{ ($planet->water_richness / config('oceane.planet.max_resources')) * 100 }}%"></div>
                                            </div>
                                        </li>
                                    </ul>
                                    
                                    @if($planet->commander_id === $commander->id)
                                        <h5 class="mt-3">Production</h5>
                                        <ul class="list-group list-group-flush bg-dark">
                                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                                <span>Production minérale:</span>
                                                <strong>{{ $planet->mineral_production ?? 0 }}/tour</strong>
                                            </li>
                                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                                <span>Production énergétique:</span>
                                                <strong>{{ $planet->energy_production ?? 0 }}/tour</strong>
                                            </li>
                                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                                <span>Revenu:</span>
                                                <strong>{{ $planet->income ?? 0 }} cr/tour</strong>
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($planet->commander_id === $commander->id)
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fas fa-building"></i> Bâtiments</h5>
                    </div>
                    <div class="card-body">
                        @if($planet->buildings && $planet->buildings->count() > 0)
                            <div class="row">
                                @foreach($planet->buildings as $building)
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-dark border-secondary building-card h-100">
                                            <div class="card-body">
                                                <h6>{{ $building->name }}</h6>
                                                <p class="small text-muted">{{ Str::limit($building->description, 80) }}</p>
                                                
                                                <div class="d-flex justify-content-between">
                                                    <span>Niveau:</span>
                                                    <strong>{{ $building->pivot->level }}</strong>
                                                </div>
                                                
                                                @if($building->pivot->is_under_construction)
                                                    <div class="mt-2">
                                                        <div class="progress position-relative" style="height: 20px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: {{ $building->pivot->construction_progress }}%"></div>
                                                            <span class="progress-time">Fin au tour {{ $building->pivot->completion_turn }}</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="mt-3 d-grid">
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#upgradeModal{{ $building->id }}">
                                                            <i class="fas fa-arrow-up"></i> Améliorer
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal d'amélioration -->
                                    <div class="modal fade" id="upgradeModal{{ $building->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content bg-dark text-light border-secondary">
                                                <div class="modal-header bg-secondary">
                                                    <h5 class="modal-title">Améliorer {{ $building->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>{{ $building->description }}</p>
                                                    
                                                    <h6>Niveau actuel: {{ $building->pivot->level }}</h6>
                                                    <p>Bonus actuels:</p>
                                                    <ul>
                                                        @foreach($building->getBonuses($building->pivot->level) as $type => $value)
                                                            <li>{{ ucfirst($type) }}: +{{ $value }}</li>
                                                        @endforeach
                                                    </ul>
                                                    
                                                    <h6>Niveau {{ $building->pivot->level + 1 }}</h6>
                                                    <p>Nouveaux bonus:</p>
                                                    <ul>
                                                        @foreach($building->getBonuses($building->pivot->level + 1) as $type => $value)
                                                            <li>{{ ucfirst($type) }}: +{{ $value }}</li>
                                                        @endforeach
                                                    </ul>
                                                    
                                                    <h6>Coûts d'amélioration:</h6>
                                                    <ul>
                                                        <li>Crédits: {{ $building->getUpgradeCost($building->pivot->level) }} cr</li>
                                                        <li>Temps: {{ $building->getUpgradeTime($building->pivot->level) }} tours</li>
                                                    </ul>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <form action="{{ route('game.buildings.upgrade', ['planet' => $planet->id, 'building' => $building->id]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-hammer"></i> Construire
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center p-4">
                                <p>Aucun bâtiment construit sur cette planète.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBuildingModal">
                                    <i class="fas fa-plus"></i> Construire un bâtiment
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fas fa-users"></i> Population</h5>
                    </div>
                    <div class="card-body">
                        @if($planet->population > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span>Population totale:</span>
                                <strong>{{ number_format($planet->population, 0) }}</strong>
                            </div>
                            <div class="progress mb-3" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ ($planet->population / $planet->max_population) * 100 }}%">
                                    {{ number_format(($planet->population / $planet->max_population) * 100, 1) }}%
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between small">
                                <span>Capacité maximum:</span>
                                <strong>{{ number_format($planet->max_population, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Croissance par tour:</span>
                                <strong>+{{ number_format($planet->population_growth, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Bonheur:</span>
                                <strong>{{ $planet->happiness }}%</strong>
                            </div>
                        @else
                            <div class="text-center">
                                <p>Cette planète n'est pas colonisée ou n'a pas de population.</p>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5><i class="fas fa-plus"></i> Nouveaux bâtiments</h5>
                    </div>
                    <div class="card-body">
                        <p>Construisez de nouveaux bâtiments pour améliorer votre planète.</p>
                        <div class="d-grid">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBuildingModal">
                                <i class="fas fa-building"></i> Construire un bâtiment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Modal de construction de nouveau bâtiment -->
@if($planet->commander_id === $commander->id)
    <div class="modal fade" id="newBuildingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title">Construire un nouveau bâtiment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @foreach(\App\Models\Building::available()->get() as $availableBuilding)
                            <div class="col-md-4 mb-3">
                                <div class="card bg-dark border-secondary h-100">
                                    <div class="card-body">
                                        <h6>{{ $availableBuilding->name }}</h6>
                                        <p class="small text-muted">{{ Str::limit($availableBuilding->description, 100) }}</p>
                                        
                                        <p><strong>Bonus:</strong></p>
                                        <ul class="small">
                                            @foreach($availableBuilding->getBonuses(1) as $type => $value)
                                                <li>{{ ucfirst($type) }}: +{{ $value }}</li>
                                            @endforeach
                                        </ul>
                                        
                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Coût:</span>
                                            <strong>{{ $availableBuilding->base_cost }} cr</strong>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Temps:</span>
                                            <strong>{{ $availableBuilding->base_build_time }} tours</strong>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-dark">
                                        <form action="{{ route('game.buildings.construct', $planet->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="building_id" value="{{ $availableBuilding->id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-primary w-100" {{ $commander->credits < $availableBuilding->base_cost ? 'disabled' : '' }}>
                                                <i class="fas fa-hammer"></i> Construire
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Élément caché contenant les données JSON de la planète pour le JavaScript -->
<script type="application/json" id="planet-data">
    {!! json_encode([
        'planet_type' => $planet->planet_type,
        'name' => $planet->name,
        'size' => $planet->size,
        'temperature' => $planet->temperature,
        'gravity' => $planet->gravity,
        'radiation' => $planet->radiation,
        'mineral_richness' => $planet->mineral_richness,
        'energy_richness' => $planet->energy_richness,
        'organic_richness' => $planet->organic_richness,
        'water_richness' => $planet->water_richness
    ]) !!}
</script>
@endsection

@push('scripts')
<script src="{{ asset('js/planet.js') }}"></script>
@endpush
