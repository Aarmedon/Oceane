@extends('layouts.app')

@section('title', $fleet->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/fleet.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('game.galaxy_map', ['galaxy_id' => $fleet->starSystem->sector->galaxy_id]) }}">
                            {{ $fleet->starSystem->sector->galaxy->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('game.star_system', $fleet->star_system_id) }}">
                            {{ $fleet->starSystem->name }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $fleet->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="fas fa-rocket"></i> 
                        {{ $fleet->name }}
                        @switch($fleet->status)
                            @case(config('oceane.fleet_status.docked'))
                                <span class="badge bg-success">À quai</span>
                                @break
                            @case(config('oceane.fleet_status.moving'))
                                <span class="badge bg-info">En mouvement</span>
                                @break
                            @case(config('oceane.fleet_status.combat'))
                                <span class="badge bg-danger">Combat</span>
                                @break
                            @case(config('oceane.fleet_status.repairing'))
                                <span class="badge bg-warning text-dark">Réparation</span>
                                @break
                            @default
                                <span class="badge bg-secondary">Inconnu</span>
                        @endswitch
                    </h4>
                    
                    <div class="btn-group">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#renameFleetModal">
                                    <i class="fas fa-edit"></i> Renommer
                                </button>
                            </li>
                            @if($fleet->status == config('oceane.fleet_status.docked'))
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#moveFleetModal">
                                        <i class="fas fa-route"></i> Déplacer
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#addShipModal">
                                        <i class="fas fa-plus"></i> Ajouter vaisseau
                                    </button>
                                </li>
                            @endif
                            <li>
                                <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#dissolveFleetModal">
                                    <i class="fas fa-trash"></i> Dissoudre
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Informations générales</h5>
                            <ul class="list-group list-group-flush bg-dark">
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Système actuel:</span>
                                    <strong>
                                        <a href="{{ route('game.star_system', $fleet->star_system_id) }}">
                                            {{ $fleet->starSystem->name }}
                                        </a>
                                    </strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Commandant:</span>
                                    <strong>{{ $fleet->commander->name }}</strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Nombre de vaisseaux:</span>
                                    <strong>{{ $fleet->ships->count() }}</strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Puissance:</span>
                                    <strong>{{ $fleet->power_rating }}</strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Entretien mensuel:</span>
                                    <strong>{{ number_format($fleet->maintenance_cost, 0) }} cr</strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Vitesse maximale:</span>
                                    <strong>{{ $fleet->max_speed }} UA/tour</strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Portée de scan:</span>
                                    <strong>{{ $fleet->scan_range }} UA</strong>
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                    <span>Expérience:</span>
                                    <strong>{{ $fleet->experience }}</strong>
                                </li>
                                @if($fleet->status == config('oceane.fleet_status.moving'))
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        <span>Destination:</span>
                                        <strong>
                                            <a href="{{ route('game.star_system', $fleet->destination_system_id) }}">
                                                {{ $fleet->destinationSystem->name }}
                                            </a>
                                        </strong>
                                        <div class="progress mt-2" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" 
                                                style="width: {{ $fleet->travel_progress }}%">
                                                {{ $fleet->travel_progress }}%
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between small mt-1">
                                            <span>Arrivée estimée:</span>
                                            <strong>Tour {{ $fleet->arrival_turn }}</strong>
                                        </div>
                                    </li>
                                @endif
                            </ul>
                            
                            @if($fleet->status == config('oceane.fleet_status.docked'))
                                <div class="d-grid gap-2 mt-3">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#moveFleetModal">
                                        <i class="fas fa-route"></i> Déplacer la flotte
                                    </button>
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addShipModal">
                                        <i class="fas fa-plus"></i> Ajouter un vaisseau
                                    </button>
                                </div>
                            @elseif($fleet->status == config('oceane.fleet_status.moving'))
                                <div class="d-grid gap-2 mt-3">
                                    <form action="{{ route('game.fleets.cancel_movement', $fleet->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="fas fa-ban"></i> Annuler le déplacement
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        
                        <div class="col-md-8">
                            <div class="fleet-display" id="fleetDisplay">
                                <!-- La visualisation de la flotte sera ajoutée par JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-ship"></i> Vaisseaux</h5>
                </div>
                <div class="card-body p-0">
                    @if($fleet->ships->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Coque</th>
                                        <th>Bouclier</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fleet->ships as $ship)
                                        <tr id="ship-row-{{ $ship->id }}" class="ship-table-row">
                                            <td>{{ $ship->name }}</td>
                                            <td>{{ $ship->design->name }}</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-danger" role="progressbar" 
                                                        style="width: {{ ($ship->hull_points / $ship->max_hull_points) * 100 }}%">
                                                        {{ $ship->hull_points }}/{{ $ship->max_hull_points }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                        style="width: {{ ($ship->shield_points / $ship->max_shield_points) * 100 }}%">
                                                        {{ $ship->shield_points }}/{{ $ship->max_shield_points }}
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($ship->status == 'operational')
                                                    <span class="badge bg-success">Opérationnel</span>
                                                @elseif($ship->status == 'damaged')
                                                    <span class="badge bg-warning text-dark">Endommagé</span>
                                                @elseif($ship->status == 'critical')
                                                    <span class="badge bg-danger">Critique</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $ship->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <button class="btn btn-sm btn-outline-info ship-details" data-ship-id="{{ $ship->id }}">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    @if($fleet->status == config('oceane.fleet_status.docked'))
                                                        <form action="{{ route('game.ships.repair', $ship->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success" {{ $ship->hull_points == $ship->max_hull_points ? 'disabled' : '' }}>
                                                                <i class="fas fa-wrench"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('game.ships.destroy', $ship->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Êtes-vous sûr de vouloir démanteler ce vaisseau ?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p>Cette flotte ne contient aucun vaisseau.</p>
                            @if($fleet->status == config('oceane.fleet_status.docked'))
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShipModal">
                                    <i class="fas fa-plus"></i> Ajouter un vaisseau
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal détails du vaisseau -->
<div class="modal fade" id="shipDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="shipDetailsTitle">Détails du vaisseau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="shipDetailsContent">
                <!-- Le contenu sera chargé dynamiquement par JavaScript -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal renommage de flotte -->
<div class="modal fade" id="renameFleetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title">Renommer la flotte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('game.fleets.rename', $fleet->id) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nouveau nom</label>
                        <input type="text" class="form-control bg-dark text-light" id="name" name="name" value="{{ $fleet->name }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Renommer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de déplacement de flotte -->
<div class="modal fade" id="moveFleetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title">Déplacer la flotte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="movement-map" id="movementMap">
                            <!-- Carte de déplacement ajoutée par JavaScript -->
                        </div>
                    </div>
                    <div class="col-md-4">
                        <form action="{{ route('game.fleets.move', $fleet->id) }}" method="POST" id="moveFleetForm">
                            @csrf
                            <input type="hidden" name="destination_system_id" id="destinationSystemId" required>
                            
                            <div class="mb-3">
                                <label class="form-label">Système actuel</label>
                                <input type="text" class="form-control bg-dark text-light" value="{{ $fleet->starSystem->name }}" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label for="selectedSystemName" class="form-label">Destination</label>
                                <input type="text" class="form-control bg-dark text-light" id="selectedSystemName" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Distance</label>
                                <input type="text" class="form-control bg-dark text-light" id="movementDistance" disabled>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Temps estimé</label>
                                <input type="text" class="form-control bg-dark text-light" id="movementTime" disabled>
                            </div>
                            
                            <div class="alert alert-info d-none" id="movementAlert">
                                <!-- Alertes affichées dynamiquement -->
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="moveFleetButton" disabled>
                                    <i class="fas fa-route"></i> Lancer le déplacement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout de vaisseau -->
<div class="modal fade" id="addShipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title">Ajouter un vaisseau</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Crédits disponibles: <strong>{{ number_format($fleet->commander->credits, 0) }} cr</strong>
                        </div>
                    </div>
                </div>
                
                <ul class="nav nav-tabs mb-3" id="shipTypesTabs" role="tablist">
                    @foreach($shipCategories as $category => $designs)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                id="{{ Str::slug($category) }}-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#{{ Str::slug($category) }}" 
                                type="button" role="tab">
                                {{ $category }}
                            </button>
                        </li>
                    @endforeach
                </ul>
                
                <div class="tab-content">
                    @foreach($shipCategories as $category => $designs)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                            id="{{ Str::slug($category) }}" 
                            role="tabpanel">
                            
                            <div class="row">
                                @foreach($designs as $design)
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-dark border-secondary h-100">
                                            <div class="card-header bg-dark text-light">
                                                <h6 class="mb-0">{{ $design->name }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="ship-thumbnail mb-2" style="background-image: url('/images/ships/{{ $design->image ?? 'default.jpg' }}')"></div>
                                                
                                                <p class="small text-muted mb-2">{{ Str::limit($design->description, 80) }}</p>
                                                
                                                <div class="ship-stats">
                                                    <div class="d-flex justify-content-between small">
                                                        <span>Coque:</span>
                                                        <strong>{{ $design->max_hull_points }}</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span>Bouclier:</span>
                                                        <strong>{{ $design->max_shield_points }}</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span>Armes:</span>
                                                        <strong>{{ $design->weapon_hardpoints }}</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between small">
                                                        <span>Entretien:</span>
                                                        <strong>{{ $design->maintenance_cost }} cr</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-dark">
                                                <form action="{{ route('game.ships.add', $fleet->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="ship_design_id" value="{{ $design->id }}">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Coût:</span>
                                                        <strong>{{ number_format($design->base_cost, 0) }} cr</strong>
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100" 
                                                        {{ $fleet->commander->credits < $design->base_cost ? 'disabled' : '' }}>
                                                        <i class="fas fa-plus"></i> Construire
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
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

<!-- Modal de dissolution de flotte -->
<div class="modal fade" id="dissolveFleetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-danger">
                <h5 class="modal-title">Dissoudre la flotte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir dissoudre cette flotte ?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Tous les vaisseaux seront transférés dans une nouvelle flotte ou démantelés.
                </div>
                
                @if($fleet->ships->count() > 0)
                    <div class="mb-3">
                        <label for="dissolveAction" class="form-label">Action pour les vaisseaux</label>
                        <select class="form-select bg-dark text-light" id="dissolveAction" name="dissolveAction">
                            <option value="transfer">Transférer vers une nouvelle flotte</option>
                            <option value="scrap">Démanteler tous les vaisseaux (récupérer 50% des ressources)</option>
                        </select>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="{{ route('game.fleets.dissolve', $fleet->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="action" id="dissolveActionValue" value="transfer">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Dissoudre
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Élément caché contenant les données JSON de la flotte pour le JavaScript -->
<script type="application/json" id="fleet-data">
    {!! json_encode([
        'fleet' => [
            'id' => $fleet->id,
            'name' => $fleet->name,
            'status' => $fleet->status,
            'star_system_id' => $fleet->star_system_id,
            'ships_count' => $fleet->ships->count(),
            'destination_system_id' => $fleet->destination_system_id ?? null,
            'travel_progress' => $fleet->travel_progress ?? 0,
            'current_position_x' => $fleet->current_position_x ?? $fleet->starSystem->position_x,
            'current_position_y' => $fleet->current_position_y ?? $fleet->starSystem->position_y,
            'max_speed' => $fleet->max_speed,
            'scan_range' => $fleet->scan_range
        ],
        'current_system' => [
            'id' => $fleet->starSystem->id,
            'name' => $fleet->starSystem->name,
            'position_x' => $fleet->starSystem->position_x,
            'position_y' => $fleet->starSystem->position_y
        ],
        'nearby_systems' => $nearbySystems ?? []
    ]) !!}
</script>

@endsection

@push('scripts')
<script src="{{ asset('js/fleet.js') }}"></script>
@endpush
