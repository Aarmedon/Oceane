@extends('layouts.app')

@section('title', $starSystem->name)

@push('styles')
<style>
    .system-view {
        background-color: #0a0e17;
        position: relative;
        width: 100%;
        height: 500px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #2d3748;
    }
    
    .star {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        box-shadow: 0 0 60px rgba(255, 255, 255, 0.8);
        z-index: 1;
    }
    
    .orbit {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .planet {
        position: absolute;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        transform-origin: center;
        z-index: 2;
    }
    
    .planet-info {
        position: absolute;
        background-color: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px;
        border-radius: 4px;
        z-index: 10;
        width: 200px;
        display: none;
    }
    
    .planet:hover .planet-info {
        display: block;
    }
    
    .fleet-marker {
        position: absolute;
        width: 12px;
        height: 12px;
        background-color: #3182ce;
        transform: rotate(45deg);
        cursor: pointer;
        z-index: 3;
    }
    
    .fleet-marker.enemy {
        background-color: #f56565;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('game.galaxy_map', ['galaxy_id' => $starSystem->sector->galaxy_id]) }}">{{ $starSystem->sector->galaxy->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $starSystem->name }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="fas fa-sun"></i> 
                        {{ $starSystem->name }}
                        @if($starSystem->commander_id === $commander->id)
                            @if($starSystem->id === $commander->capital_system_id)
                                <span class="badge bg-success"><i class="fas fa-crown"></i> Capitale</span>
                            @else
                                <span class="badge bg-success">Votre système</span>
                            @endif
                        @elseif($starSystem->commander_id)
                            <span class="badge bg-danger">Système ennemi</span>
                        @else
                            <span class="badge bg-warning text-dark">Système neutre</span>
                        @endif
                    </h4>
                    <div>
                        @if($starSystem->commander_id === $commander->id)
                            <div class="btn-group">
                                <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-dark">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('game.fleets.create', ['system_id' => $starSystem->id]) }}">
                                            <i class="fas fa-plus"></i> Créer une flotte
                                        </a>
                                    </li>
                                    @if($starSystem->id !== $commander->capital_system_id)
                                        <li>
                                            <form action="{{ route('game.set_capital', $starSystem->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-crown"></i> Définir comme capitale
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                        
                        @if($fleets->where('commander_id', $commander->id)->count() > 0)
                            <a href="{{ route('game.fleets.create', ['system_id' => $starSystem->id]) }}" class="btn btn-outline-primary">
                                <i class="fas fa-plus"></i> Créer une flotte
                            </a>
                        @endif
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Informations générales</h5>
                            <ul class="list-group list-group-flush bg-dark">
                                <li class="list-group-item bg-dark text-light border-secondary">
                                    <strong>Secteur:</strong> {{ $starSystem->sector->name }}
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary">
                                    <strong>Galaxie:</strong> {{ $starSystem->sector->galaxy->name }}
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary">
                                    <strong>Type d'étoile:</strong> {{ config('oceane.star_types.' . $starSystem->star_type) }}
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary">
                                    <strong>Position:</strong> ({{ $starSystem->position_x }}, {{ $starSystem->position_y }})
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary">
                                    <strong>Planètes:</strong> {{ $starSystem->planets->count() }}
                                </li>
                                <li class="list-group-item bg-dark text-light border-secondary">
                                    <strong>Propriétaire:</strong> 
                                    @if($starSystem->commander_id)
                                        {{ $starSystem->commander->name }}
                                    @else
                                        Aucun
                                    @endif
                                </li>
                                @if($isVisible && $starSystem->commander_id === $commander->id)
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        <strong>Taux d'imposition:</strong> {{ $starSystem->tax_rate }}%
                                        <button type="button" class="btn btn-sm btn-outline-light ms-2" data-bs-toggle="modal" data-bs-target="#taxRateModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </li>
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        <strong>Revenu mensuel:</strong> {{ number_format($starSystem->income, 0) }} cr
                                    </li>
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        <strong>Coût d'entretien:</strong> {{ number_format($starSystem->maintenance_cost, 0) }} cr
                                    </li>
                                @endif
                            </ul>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="system-view" id="systemView">
                                <!-- Star -->
                                <div class="star" id="star"></div>
                                
                                <!-- Planets (will be added by JS) -->
                                
                                <!-- Fleet markers (will be added by JS) -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-planet"></i> Planètes</h5>
                </div>
                <div class="card-body p-0">
                    @if($starSystem->planets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Taille</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($starSystem->planets as $planet)
                                        <tr id="planet-row-{{ $planet->id }}" class="planet-table-row">
                                            <td>{{ $planet->name }}</td>
                                            <td>{{ config('oceane.planet.types.' . $planet->planet_type) }}</td>
                                            <td>{{ $planet->size }}</td>
                                            <td>
                                                @if($planet->commander_id === $commander->id)
                                                    <span class="badge bg-success">Votre planète</span>
                                                @elseif($planet->commander_id)
                                                    <span class="badge bg-danger">Planète ennemie</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Inoccupée</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('game.planet', $planet->id) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p>Ce système stellaire ne contient aucune planète.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-rocket"></i> Flottes présentes</h5>
                </div>
                <div class="card-body p-0">
                    @if($fleets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Commandant</th>
                                        <th>Vaisseaux</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fleets as $fleet)
                                        <tr id="fleet-row-{{ $fleet->id }}" class="fleet-table-row">
                                            <td>{{ $fleet->name }}</td>
                                            <td>
                                                @if($fleet->commander_id === $commander->id)
                                                    <span class="text-success">Vous</span>
                                                @else
                                                    {{ $fleet->commander->name }}
                                                @endif
                                            </td>
                                            <td>{{ $fleet->ships->count() }}</td>
                                            <td>
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
                                            </td>
                                            <td>
                                                @if($fleet->commander_id === $commander->id)
                                                    <a href="{{ route('game.fleets.show', $fleet->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @else
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-4 text-center">
                            <p>Aucune flotte présente dans ce système.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tax Rate Modal -->
@if($isVisible && $starSystem->commander_id === $commander->id)
    <div class="modal fade" id="taxRateModal" tabindex="-1" aria-labelledby="taxRateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header bg-secondary">
                    <h5 class="modal-title" id="taxRateModalLabel">Ajuster le taux d'imposition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('game.update_tax_rate', $starSystem->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <p>Le taux d'imposition affecte les revenus du système, mais attention : un taux élevé peut provoquer du mécontentement.</p>
                        
                        <div class="mb-3">
                            <label for="tax_rate" class="form-label">Taux d'imposition (%)</label>
                            <input type="range" class="form-range" id="tax_rate" name="tax_rate" min="0" max="10" step="1" value="{{ $starSystem->tax_rate }}">
                            <div class="d-flex justify-content-between">
                                <span>0%</span>
                                <span id="taxRateValue">{{ $starSystem->tax_rate }}%</span>
                                <span>10%</span>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="d-flex justify-content-between">
                                <span>Revenu estimé:</span>
                                <span id="estimatedIncome">{{ number_format($starSystem->income, 0) }} cr</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data from server
        const starSystem = @json($starSystem);
        const planets = @json($starSystem->planets);
        const fleets = @json($fleets);
        const commander = @json($commander);
        
        // System view
        const systemView = document.getElementById('systemView');
        const viewWidth = systemView.offsetWidth;
        const viewHeight = systemView.offsetHeight;
        const centerX = viewWidth / 2;
        const centerY = viewHeight / 2;
        
        // Star
        const star = document.getElementById('star');
        const starSize = 60;
        star.style.width = starSize + 'px';
        star.style.height = starSize + 'px';
        
        // Set star color based on type
        switch(starSystem.star_type) {
            case 0: // Naine rouge
                star.style.backgroundColor = '#ff5a5a';
                break;
            case 1: // Naine jaune
                star.style.backgroundColor = '#ffde59';
                break;
            case 2: // Géante bleue
                star.style.backgroundColor = '#59c2ff';
                break;
            case 3: // Géante rouge
                star.style.backgroundColor = '#ff3a3a';
                star.style.width = (starSize * 1.5) + 'px';
                star.style.height = (starSize * 1.5) + 'px';
                break;
            case 4: // Étoile binaire
                star.style.backgroundColor = '#ffde59';
                // Create second star
                const secondStar = document.createElement('div');
                secondStar.className = 'star';
                secondStar.style.width = (starSize * 0.8) + 'px';
                secondStar.style.height = (starSize * 0.8) + 'px';
                secondStar.style.backgroundColor = '#ff5a5a';
                secondStar.style.transform = 'translate(-70%, -30%)';
                systemView.appendChild(secondStar);
                break;
            case 5: // Pulsar
                star.style.backgroundColor = '#ffffff';
                star.style.animation = 'pulse 2s infinite';
                const style = document.createElement('style');
                style.innerHTML = `
                    @keyframes pulse {
                        0% { box-shadow: 0 0 60px rgba(255, 255, 255, 0.8); }
                        50% { box-shadow: 0 0 30px rgba(255, 255, 255, 0.4); }
                        100% { box-shadow: 0 0 60px rgba(255, 255, 255, 0.8); }
                    }
                `;
                document.head.appendChild(style);
                break;
            default:
                star.style.backgroundColor = '#ffffff';
        }
        
        // Create orbits and planets
        planets.forEach((planet, index) => {
            // Create orbit
            const orbitRadius = 80 + (index * 40);
            const orbit = document.createElement('div');
            orbit.className = 'orbit';
            orbit.style.width = (orbitRadius * 2) + 'px';
            orbit.style.height = (orbitRadius * 2) + 'px';
            systemView.appendChild(orbit);
            
            // Create planet
            const planetSize = 10 + (planet.size * 2);
            const planetAngle = Math.random() * 360;
            const planetX = centerX + orbitRadius * Math.cos(planetAngle * Math.PI / 180);
            const planetY = centerY + orbitRadius * Math.sin(planetAngle * Math.PI / 180);
            
            const planetElement = document.createElement('div');
            planetElement.className = 'planet';
            planetElement.setAttribute('data-id', planet.id);
            planetElement.style.width = planetSize + 'px';
            planetElement.style.height = planetSize + 'px';
            planetElement.style.left = (planetX - (planetSize/2)) + 'px';
            planetElement.style.top = (planetY - (planetSize/2)) + 'px';
            
            // Set planet color based on type
            switch(planet.planet_type) {
                case 0: // Terrestre
                    planetElement.style.backgroundColor = '#4ade80';
                    break;
                case 1: // Océanique
                    planetElement.style.backgroundColor = '#3b82f6';
                    break;
                case 2: // Désertique
                    planetElement.style.backgroundColor = '#fcd34d';
                    break;
                case 3: // Jungle
                    planetElement.style.backgroundColor = '#15803d';
                    break;
                case 4: // Rocheuse
                    planetElement.style.backgroundColor = '#78716c';
                    break;
                case 5: // Gazeuse
                    planetElement.style.backgroundColor = '#c4b5fd';
                    break;
                case 6: // Volcanique
                    planetElement.style.backgroundColor = '#f43f5e';
                    break;
                case 7: // Glaciale
                    planetElement.style.backgroundColor = '#e0f2fe';
                    break;
                default:
                    planetElement.style.backgroundColor = '#6b7280';
            }
            
            // Set owner styling
            if(planet.commander_id === commander.id) {
                planetElement.style.border = '2px solid #48bb78';
            } else if(planet.commander_id) {
                planetElement.style.border = '2px solid #f56565';
            }
            
            // Planet info tooltip
            const infoElement = document.createElement('div');
            infoElement.className = 'planet-info';
            infoElement.innerHTML = `
                <h6>${planet.name}</h6>
                <p><strong>Type:</strong> ${planet.planet_type_name}</p>
                <p><strong>Taille:</strong> ${planet.size}</p>
                ${planet.commander_id ? `<p><strong>Propriétaire:</strong> ${planet.commander_id === commander.id ? 'Vous' : 'Ennemi'}</p>` : ''}
            `;
            planetElement.appendChild(infoElement);
            
            // Click to view planet
            planetElement.addEventListener('click', function() {
                window.location.href = `/game/planets/${planet.id}`;
            });
            
            systemView.appendChild(planetElement);
            
            // Highlight corresponding table row on hover
            planetElement.addEventListener('mouseenter', function() {
                const row = document.getElementById('planet-row-' + planet.id);
                if (row) row.classList.add('table-active');
            });
            
            planetElement.addEventListener('mouseleave', function() {
                const row = document.getElementById('planet-row-' + planet.id);
                if (row) row.classList.remove('table-active');
            });
        });
        
        // Add fleet markers
        fleets.forEach((fleet, index) => {
            const fleetElement = document.createElement('div');
            fleetElement.className = fleet.commander_id === commander.id ? 'fleet-marker' : 'fleet-marker enemy';
            
            // Position fleet at random location near the system center
            const angle = (index / fleets.length) * 360;
            const distance = 40;
            const x = centerX + distance * Math.cos(angle * Math.PI / 180);
            const y = centerY + distance * Math.sin(angle * Math.PI / 180);
            
            fleetElement.style.left = x + 'px';
            fleetElement.style.top = y + 'px';
            fleetElement.setAttribute('data-id', fleet.id);
            
            // Click to view fleet
            fleetElement.addEventListener('click', function() {
                if (fleet.commander_id === commander.id) {
                    window.location.href = `/game/fleets/${fleet.id}`;
                }
            });
            
            systemView.appendChild(fleetElement);
            
            // Highlight corresponding table row on hover
            fleetElement.addEventListener('mouseenter', function() {
                const row = document.getElementById('fleet-row-' + fleet.id);
                if (row) row.classList.add('table-active');
            });
            
            fleetElement.addEventListener('mouseleave', function() {
                const row = document.getElementById('fleet-row-' + fleet.id);
                if (row) row.classList.remove('table-active');
            });
        });
        
        // Tax rate slider functionality
        const taxRateSlider = document.getElementById('tax_rate');
        const taxRateValue = document.getElementById('taxRateValue');
        const estimatedIncome = document.getElementById('estimatedIncome');
        
        if (taxRateSlider) {
            taxRateSlider.addEventListener('input', function() {
                const baseIncome = {{ $starSystem->base_income ?? 500 }};
                const newRate = parseInt(this.value);
                taxRateValue.textContent = newRate + '%';
                
                // Calculate new income
                const newIncome = Math.round(baseIncome * (newRate / 100 * 2));
                estimatedIncome.textContent = newIncome.toLocaleString() + ' cr';
            });
        }
    });
</script>
@endpush
