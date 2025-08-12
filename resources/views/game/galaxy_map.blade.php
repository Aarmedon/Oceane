@extends('layouts.app')

@section('title', 'Carte Galactique')

@push('styles')
<style>
    .galaxy-map {
        background-color: #0a0e17;
        position: relative;
        width: 100%;
        height: 600px;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid #2d3748;
    }
    
    .star-system {
        position: absolute;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .star-system .star {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #fff;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.7);
        margin: auto;
    }
    
    .star-system.owned .star {
        background-color: #48bb78;
        box-shadow: 0 0 10px rgba(72, 187, 120, 0.7);
    }
    
    .star-system.enemy .star {
        background-color: #f56565;
        box-shadow: 0 0 10px rgba(245, 101, 101, 0.7);
    }
    
    .star-system.neutral .star {
        background-color: #d69e2e;
        box-shadow: 0 0 10px rgba(214, 158, 46, 0.7);
    }
    
    .star-system.capital .star {
        width: 12px;
        height: 12px;
        box-shadow: 0 0 15px rgba(72, 187, 120, 0.9);
    }
    
    .star-system:hover {
        z-index: 10;
        transform: scale(1.5);
    }
    
    .star-system:hover .system-name {
        opacity: 1;
        visibility: visible;
    }
    
    .system-name {
        position: absolute;
        top: 10px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.2s ease;
        z-index: 5;
    }
    
    .fleet {
        position: absolute;
        width: 12px;
        height: 12px;
        border-radius: 3px;
        background-color: #3182ce;
        z-index: 5;
        transform: rotate(45deg);
        cursor: pointer;
    }
    
    .map-controls {
        position: absolute;
        bottom: 10px;
        right: 10px;
        z-index: 20;
    }
    
    .map-legend {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: rgba(0, 0, 0, 0.7);
        border-radius: 8px;
        padding: 10px;
        z-index: 20;
        font-size: 12px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }
    
    .legend-color {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .sector-grid {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    
    .sector-line {
        position: absolute;
        background-color: rgba(75, 85, 99, 0.3);
    }
    
    .sector-label {
        position: absolute;
        color: rgba(156, 163, 175, 0.7);
        font-size: 10px;
        pointer-events: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-globe"></i> Carte de la galaxie: {{ $galaxy->name }}</h4>
                    <div>
                        <div class="dropdown d-inline-block me-2">
                            <button class="btn btn-dark dropdown-toggle" type="button" id="galaxyDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Changer de galaxie
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="galaxyDropdown">
                                @foreach($galaxies as $g)
                                    <li>
                                        <a class="dropdown-item {{ $g->id === $galaxy->id ? 'active' : '' }}" href="{{ route('game.galaxy_map', ['galaxy_id' => $g->id]) }}">
                                            {{ $g->name }}
                                            @if($g->id === $galaxy->id)
                                                <i class="fas fa-check ms-2"></i>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-light" id="toggleGrid">
                                <i class="fas fa-th"></i> Grille
                            </button>
                            <button type="button" class="btn btn-outline-light" id="toggleNames">
                                <i class="fas fa-tag"></i> Noms
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="galaxy-map" id="galaxyMap">
                        <!-- Map Legend -->
                        <div class="map-legend">
                            <h6 class="text-light mb-2">Légende</h6>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #48bb78;"></div>
                                <span>Vos systèmes</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #f56565;"></div>
                                <span>Systèmes ennemis</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #d69e2e;"></div>
                                <span>Systèmes neutres</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #3182ce;"></div>
                                <span>Vos flottes</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="width: 12px; height: 12px; border-radius: 0; transform: rotate(45deg); background-color: #3182ce;"></div>
                                <span>Flottes en mouvement</span>
                            </div>
                        </div>
                        
                        <!-- Map Controls -->
                        <div class="map-controls">
                            <div class="btn-group">
                                <button class="btn btn-dark" id="zoomIn"><i class="fas fa-search-plus"></i></button>
                                <button class="btn btn-dark" id="zoomOut"><i class="fas fa-search-minus"></i></button>
                                <button class="btn btn-dark" id="resetView"><i class="fas fa-sync"></i></button>
                            </div>
                        </div>
                        
                        <!-- Sector Grid (will be generated by JS) -->
                        <div class="sector-grid" id="sectorGrid"></div>
                        
                        <!-- Star Systems (will be populated by JS) -->
                        <!-- Fleets (will be populated by JS) -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-sun"></i> Systèmes stellaires</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Secteur</th>
                                    <th>Type d'étoile</th>
                                    <th>Planètes</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($starSystems as $system)
                                    <tr id="system-row-{{ $system->id }}" class="system-table-row">
                                        <td>{{ $system->name }}</td>
                                        <td>{{ $system->sector->name }}</td>
                                        <td>{{ config('oceane.star_types.' . $system->star_type) }}</td>
                                        <td>{{ $system->planets->count() }}</td>
                                        <td>
                                            @if($system->commander_id === $commander->id)
                                                <span class="badge bg-success">Votre système</span>
                                            @elseif($system->commander_id)
                                                <span class="badge bg-danger">Système ennemi</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Neutre</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('game.star_system', $system->id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-rocket"></i> Vos flottes dans cette galaxie</h5>
                </div>
                <div class="card-body p-0">
                    @if($fleets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Position</th>
                                        <th>Statut</th>
                                        <th>Vaisseaux</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fleets as $fleet)
                                        <tr id="fleet-row-{{ $fleet->id }}" class="fleet-table-row">
                                            <td>{{ $fleet->name }}</td>
                                            <td>
                                                @if($fleet->current_system_id)
                                                    <a href="{{ route('game.star_system', $fleet->current_system_id) }}">
                                                        {{ $fleet->currentSystem->name }}
                                                    </a>
                                                @elseif($fleet->status == config('oceane.fleet_status.moving'))
                                                    En transit vers 
                                                    <a href="{{ route('game.star_system', $fleet->destination_system_id) }}">
                                                        {{ $fleet->destinationSystem->name }}
                                                    </a>
                                                @else
                                                    Inconnue
                                                @endif
                                            </td>
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
                                            <td>{{ $fleet->ships->count() }}</td>
                                            <td>
                                                <a href="{{ route('game.fleets.show', $fleet->id) }}" class="btn btn-sm btn-outline-primary">
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
                            <p>Vous n'avez pas de flottes dans cette galaxie.</p>
                            <a href="{{ route('game.fleets') }}" class="btn btn-outline-primary">
                                <i class="fas fa-rocket"></i> Gérer mes flottes
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Info Modal -->
<div class="modal fade" id="systemInfoModal" tabindex="-1" aria-labelledby="systemInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="systemInfoModalLabel">Informations système</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="systemInfoBody">
                <div class="text-center">
                    <div class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <a href="#" id="viewSystemButton" class="btn btn-primary">
                    <i class="fas fa-eye"></i> Voir le système
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data from server
        const galaxyData = @json($galaxy);
        const sectors = @json($sectors);
        const starSystems = @json($starSystems);
        const fleets = @json($fleets);
        const commander = @json($commander);
        
        // Map settings
        const mapContainer = document.getElementById('galaxyMap');
        const mapWidth = mapContainer.offsetWidth;
        const mapHeight = mapContainer.offsetHeight;
        const galaxySizeX = {{ config('oceane.galaxy.size_x') }};
        const galaxySizeY = {{ config('oceane.galaxy.size_y') }};
        
        let scale = 1;
        let translateX = 0;
        let translateY = 0;
        let showNames = false;
        let showGrid = true;
        
        // Initialize the map
        initMap();
        
        function initMap() {
            // Create sector grid
            if (showGrid) {
                createSectorGrid();
            }
            
            // Create star systems
            createStarSystems();
            
            // Create fleets
            createFleets();
            
            // Find commander's capital system
            const capitalSystem = starSystems.find(sys => sys.id === commander.capital_system_id);
            if (capitalSystem) {
                // Center map on capital
                centerOnSystem(capitalSystem);
            }
        }
        
        function createSectorGrid() {
            const sectorGrid = document.getElementById('sectorGrid');
            sectorGrid.innerHTML = '';
            
            // Create sector lines and labels
            sectors.forEach(sector => {
                // Convert sector boundaries to screen coordinates
                const x1 = (sector.start_x / galaxySizeX) * mapWidth;
                const y1 = (sector.start_y / galaxySizeY) * mapHeight;
                const x2 = (sector.end_x / galaxySizeX) * mapWidth;
                const y2 = (sector.end_y / galaxySizeY) * mapHeight;
                
                // Horizontal line at top
                const topLine = document.createElement('div');
                topLine.className = 'sector-line';
                topLine.style.top = y1 + 'px';
                topLine.style.left = x1 + 'px';
                topLine.style.width = (x2 - x1) + 'px';
                topLine.style.height = '1px';
                sectorGrid.appendChild(topLine);
                
                // Horizontal line at bottom
                const bottomLine = document.createElement('div');
                bottomLine.className = 'sector-line';
                bottomLine.style.top = y2 + 'px';
                bottomLine.style.left = x1 + 'px';
                bottomLine.style.width = (x2 - x1) + 'px';
                bottomLine.style.height = '1px';
                sectorGrid.appendChild(bottomLine);
                
                // Vertical line at left
                const leftLine = document.createElement('div');
                leftLine.className = 'sector-line';
                leftLine.style.top = y1 + 'px';
                leftLine.style.left = x1 + 'px';
                leftLine.style.width = '1px';
                leftLine.style.height = (y2 - y1) + 'px';
                sectorGrid.appendChild(leftLine);
                
                // Vertical line at right
                const rightLine = document.createElement('div');
                rightLine.className = 'sector-line';
                rightLine.style.top = y1 + 'px';
                rightLine.style.left = x2 + 'px';
                rightLine.style.width = '1px';
                rightLine.style.height = (y2 - y1) + 'px';
                sectorGrid.appendChild(rightLine);
                
                // Sector label
                const label = document.createElement('div');
                label.className = 'sector-label';
                label.textContent = sector.name;
                label.style.top = (y1 + 5) + 'px';
                label.style.left = (x1 + 5) + 'px';
                sectorGrid.appendChild(label);
            });
        }
        
        function createStarSystems() {
            starSystems.forEach(system => {
                // Convert system position to screen coordinates
                const x = (system.position_x / galaxySizeX) * mapWidth;
                const y = (system.position_y / galaxySizeY) * mapHeight;
                
                // Determine system class
                let systemClass = 'neutral';
                if (system.commander_id === commander.id) {
                    systemClass = 'owned';
                } else if (system.commander_id) {
                    systemClass = 'enemy';
                }
                
                // Create star system element
                const systemElement = document.createElement('div');
                systemElement.className = `star-system ${systemClass}`;
                if (system.id === commander.capital_system_id) {
                    systemElement.classList.add('capital');
                }
                systemElement.style.left = (x - 4) + 'px';
                systemElement.style.top = (y - 4) + 'px';
                systemElement.setAttribute('data-id', system.id);
                
                // Create star
                const star = document.createElement('div');
                star.className = 'star';
                systemElement.appendChild(star);
                
                // Create system name
                const nameElement = document.createElement('div');
                nameElement.className = 'system-name';
                nameElement.textContent = system.name;
                if (showNames) {
                    nameElement.style.opacity = '1';
                    nameElement.style.visibility = 'visible';
                }
                systemElement.appendChild(nameElement);
                
                // Add click event
                systemElement.addEventListener('click', function() {
                    showSystemInfo(system);
                });
                
                // Add to map
                mapContainer.appendChild(systemElement);
                
                // Highlight corresponding table row on hover
                systemElement.addEventListener('mouseenter', function() {
                    const row = document.getElementById('system-row-' + system.id);
                    if (row) row.classList.add('table-active');
                });
                
                systemElement.addEventListener('mouseleave', function() {
                    const row = document.getElementById('system-row-' + system.id);
                    if (row) row.classList.remove('table-active');
                });
            });
        }
        
        function createFleets() {
            fleets.forEach(fleet => {
                if (fleet.current_system_id) {
                    const system = starSystems.find(sys => sys.id === fleet.current_system_id);
                    if (system) {
                        // Convert system position to screen coordinates
                        const x = (system.position_x / galaxySizeX) * mapWidth;
                        const y = (system.position_y / galaxySizeY) * mapHeight;
                        
                        // Create fleet element
                        const fleetElement = document.createElement('div');
                        fleetElement.className = 'fleet';
                        fleetElement.style.left = (x + 8) + 'px';
                        fleetElement.style.top = (y - 6) + 'px';
                        fleetElement.setAttribute('data-id', fleet.id);
                        
                        // Add click event
                        fleetElement.addEventListener('click', function(e) {
                            e.stopPropagation();
                            window.location.href = '/game/fleets/' + fleet.id;
                        });
                        
                        // Add to map
                        mapContainer.appendChild(fleetElement);
                        
                        // Highlight corresponding table row on hover
                        fleetElement.addEventListener('mouseenter', function() {
                            const row = document.getElementById('fleet-row-' + fleet.id);
                            if (row) row.classList.add('table-active');
                        });
                        
                        fleetElement.addEventListener('mouseleave', function() {
                            const row = document.getElementById('fleet-row-' + fleet.id);
                            if (row) row.classList.remove('table-active');
                        });
                    }
                }
            });
        }
        
        function showSystemInfo(system) {
            const modal = new bootstrap.Modal(document.getElementById('systemInfoModal'));
            const modalBody = document.getElementById('systemInfoBody');
            const viewSystemButton = document.getElementById('viewSystemButton');
            
            // Update modal content
            modalBody.innerHTML = `
                <h5>${system.name}</h5>
                <p><strong>Secteur:</strong> ${system.sector.name}</p>
                <p><strong>Type d'étoile:</strong> ${system.star_type_name || 'Inconnu'}</p>
                <p><strong>Planètes:</strong> ${system.planets_count || 0}</p>
                <p><strong>Statut:</strong> 
                    ${system.commander_id === commander.id 
                      ? '<span class="badge bg-success">Votre système</span>' 
                      : system.commander_id 
                        ? '<span class="badge bg-danger">Système ennemi</span>' 
                        : '<span class="badge bg-warning text-dark">Neutre</span>'}
                </p>
                <p><strong>Position:</strong> (${system.position_x}, ${system.position_y})</p>
            `;
            
            // Set button link
            viewSystemButton.href = '/game/star_systems/' + system.id;
            
            // Show modal
            modal.show();
        }
        
        function centerOnSystem(system) {
            // Convert system position to screen coordinates
            const x = (system.position_x / galaxySizeX) * mapWidth;
            const y = (system.position_y / galaxySizeY) * mapHeight;
            
            // Center map on this position
            translateX = (mapWidth / 2) - x;
            translateY = (mapHeight / 2) - y;
            
            // Apply transform
            applyTransform();
        }
        
        function applyTransform() {
            // Apply transform to all elements in the map
            Array.from(mapContainer.children).forEach(element => {
                element.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
            });
        }
        
        // Map controls
        document.getElementById('zoomIn').addEventListener('click', function() {
            scale += 0.2;
            if (scale > 3) scale = 3;
            applyTransform();
        });
        
        document.getElementById('zoomOut').addEventListener('click', function() {
            scale -= 0.2;
            if (scale < 0.5) scale = 0.5;
            applyTransform();
        });
        
        document.getElementById('resetView').addEventListener('click', function() {
            scale = 1;
            translateX = 0;
            translateY = 0;
            applyTransform();
        });
        
        document.getElementById('toggleNames').addEventListener('click', function() {
            showNames = !showNames;
            document.querySelectorAll('.system-name').forEach(nameElement => {
                if (showNames) {
                    nameElement.style.opacity = '1';
                    nameElement.style.visibility = 'visible';
                } else {
                    nameElement.style.opacity = '0';
                    nameElement.style.visibility = 'hidden';
                }
            });
            this.classList.toggle('active');
        });
        
        document.getElementById('toggleGrid').addEventListener('click', function() {
            showGrid = !showGrid;
            const sectorGrid = document.getElementById('sectorGrid');
            sectorGrid.style.display = showGrid ? 'block' : 'none';
            this.classList.toggle('active');
        });
        
        // Drag functionality
        let isDragging = false;
        let startX, startY;
        let lastTranslateX = translateX;
        let lastTranslateY = translateY;
        
        mapContainer.addEventListener('mousedown', function(e) {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            lastTranslateX = translateX;
            lastTranslateY = translateY;
            mapContainer.style.cursor = 'grabbing';
        });
        
        document.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            
            translateX = lastTranslateX + (e.clientX - startX);
            translateY = lastTranslateY + (e.clientY - startY);
            
            applyTransform();
        });
        
        document.addEventListener('mouseup', function() {
            isDragging = false;
            mapContainer.style.cursor = 'grab';
        });
        
        // Highlight table row when hovering on map element
        document.querySelectorAll('.system-table-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                const id = this.id.replace('system-row-', '');
                const systemElement = document.querySelector(`.star-system[data-id="${id}"]`);
                if (systemElement) systemElement.style.transform = 'scale(1.5)';
            });
            
            row.addEventListener('mouseleave', function() {
                const id = this.id.replace('system-row-', '');
                const systemElement = document.querySelector(`.star-system[data-id="${id}"]`);
                if (systemElement) systemElement.style.transform = '';
            });
        });
        
        document.querySelectorAll('.fleet-table-row').forEach(row => {
            row.addEventListener('mouseenter', function() {
                const id = this.id.replace('fleet-row-', '');
                const fleetElement = document.querySelector(`.fleet[data-id="${id}"]`);
                if (fleetElement) fleetElement.style.transform = 'scale(1.5) rotate(45deg)';
            });
            
            row.addEventListener('mouseleave', function() {
                const id = this.id.replace('fleet-row-', '');
                const fleetElement = document.querySelector(`.fleet[data-id="${id}"]`);
                if (fleetElement) fleetElement.style.transform = 'rotate(45deg)';
            });
        });
    });
</script>
@endpush
