@extends('game.orders.create.layout')

@php
$orderTypeTitle = 'Construction';
$orderType = 'build';
$orderTypeIcon = 'fa-hammer';
$instructions = 'Sélectionnez le type de construction (bâtiment ou vaisseau), puis choisissez l\'élément à construire et l\'emplacement. La construction débutera au prochain tour.';
@endphp

@section('order_form')
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="build_type" class="form-label">Type de construction</label>
            <select class="form-select bg-dark text-light border-secondary" id="build_type" name="build_type" required>
                <option value="">Sélectionnez un type</option>
                <option value="building">Bâtiment</option>
                <option value="ship">Vaisseau</option>
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un type de construction.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="location_id" class="form-label">Emplacement</label>
            <select class="form-select bg-dark text-light border-secondary" id="location_id" name="location_id" required>
                <option value="">Sélectionnez un emplacement</option>
                @foreach($planets as $planet)
                <option value="{{ $planet->id }}" data-resources="{{ json_encode($planet->resources) }}" data-capacity="{{ $planet->construction_capacity }}">
                    {{ $planet->name }} ({{ $planet->system_name }})
                </option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un emplacement.
            </div>
        </div>
        
        <!-- Sélecteur de bâtiment (affiché conditionnellement) -->
        <div class="form-group mb-3 d-none" id="building-select-group">
            <label for="building_id" class="form-label">Bâtiment</label>
            <select class="form-select bg-dark text-light border-secondary" id="building_id" name="building_id">
                <option value="">Sélectionnez un bâtiment</option>
                @foreach($buildingCategories as $category => $buildings)
                <optgroup label="{{ $category }}">
                    @foreach($buildings as $building)
                    <option value="{{ $building->id }}" 
                            data-name="{{ $building->name }}"
                            data-description="{{ $building->description }}"
                            data-cost="{{ json_encode($building->cost) }}"
                            data-construction-time="{{ $building->construction_time }}"
                            data-icon="{{ $building->icon }}"
                            data-max-level="{{ $building->max_level }}">
                        {{ $building->name }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un bâtiment.
            </div>
        </div>
        
        <!-- Niveau du bâtiment (affiché conditionnellement) -->
        <div class="form-group mb-3 d-none" id="building-level-group">
            <label for="building_level" class="form-label">Niveau</label>
            <select class="form-select bg-dark text-light border-secondary" id="building_level" name="building_level">
                <option value="">Sélectionnez un niveau</option>
                <!-- Les options seront ajoutées dynamiquement -->
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un niveau.
            </div>
        </div>
        
        <!-- Sélecteur de vaisseau (affiché conditionnellement) -->
        <div class="form-group mb-3 d-none" id="ship-select-group">
            <label for="ship_design_id" class="form-label">Conception de vaisseau</label>
            <select class="form-select bg-dark text-light border-secondary" id="ship_design_id" name="ship_design_id">
                <option value="">Sélectionnez une conception</option>
                @foreach($shipCategories as $category => $designs)
                <optgroup label="{{ $category }}">
                    @foreach($designs as $design)
                    <option value="{{ $design->id }}" 
                            data-name="{{ $design->name }}"
                            data-description="{{ $design->description }}"
                            data-cost="{{ json_encode($design->cost) }}"
                            data-construction-time="{{ $design->construction_time }}"
                            data-class="{{ $design->ship_class }}"
                            data-components="{{ json_encode($design->components) }}">
                        {{ $design->name }} ({{ $design->ship_class }})
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner une conception de vaisseau.
            </div>
        </div>
        
        <!-- Quantité de vaisseaux (affiché conditionnellement) -->
        <div class="form-group mb-3 d-none" id="ship-quantity-group">
            <label for="quantity" class="form-label">Quantité</label>
            <input type="number" class="form-control bg-dark text-light border-secondary" id="quantity" name="quantity" min="1" value="1">
            <div class="invalid-feedback">
                Veuillez entrer une quantité valide.
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Détails de la construction</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4" id="construction-placeholder">
                    <i class="fas fa-hammer fa-3x mb-3"></i>
                    <p>Sélectionnez un type de construction et un élément pour afficher les détails</p>
                </div>
                
                <div id="construction-info" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <div class="construction-icon me-3">
                            <i class="fas fa-2x" id="construction-icon"></i>
                        </div>
                        <div>
                            <h5 id="construction-name" class="mb-0"></h5>
                            <div class="text-muted small" id="construction-subtext"></div>
                        </div>
                    </div>
                    
                    <div class="construction-description mb-3" id="construction-description"></div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Temps de construction</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="construction-time" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">tour(s)</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Capacité de construction</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="construction-capacity" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">pts/tour</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mb-2">Coût de construction</h6>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="resource-item">
                                <div class="d-flex justify-content-between">
                                    <span><i class="fas fa-gem text-info me-1"></i> Minéraux</span>
                                    <span id="cost-minerals">0</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-info" id="minerals-progress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="resource-item">
                                <div class="d-flex justify-content-between">
                                    <span><i class="fas fa-atom text-warning me-1"></i> Gaz</span>
                                    <span id="cost-gas">0</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-warning" id="gas-progress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="resource-item">
                                <div class="d-flex justify-content-between">
                                    <span><i class="fas fa-coins text-success me-1"></i> Crédits</span>
                                    <span id="cost-credits">0</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" id="credits-progress" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-dark border-secondary" id="ship-components-card">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Composants du vaisseau</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4" id="components-placeholder">
                    <i class="fas fa-cogs fa-3x mb-3"></i>
                    <p>Sélectionnez une conception de vaisseau pour afficher les composants</p>
                </div>
                
                <div id="components-info" class="d-none">
                    <ul class="list-group list-group-flush bg-transparent" id="components-list">
                        <!-- Les composants seront ajoutés ici dynamiquement -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('order_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const buildTypeSelect = document.getElementById('build_type');
    const locationSelect = document.getElementById('location_id');
    const buildingSelectGroup = document.getElementById('building-select-group');
    const buildingSelect = document.getElementById('building_id');
    const buildingLevelGroup = document.getElementById('building-level-group');
    const buildingLevelSelect = document.getElementById('building_level');
    const shipSelectGroup = document.getElementById('ship-select-group');
    const shipSelect = document.getElementById('ship_design_id');
    const shipQuantityGroup = document.getElementById('ship-quantity-group');
    const quantityInput = document.getElementById('quantity');
    const shipComponentsCard = document.getElementById('ship-components-card');
    
    const constructionPlaceholder = document.getElementById('construction-placeholder');
    const constructionInfo = document.getElementById('construction-info');
    const componentsPlaceholder = document.getElementById('components-placeholder');
    const componentsInfo = document.getElementById('components-info');
    const componentsList = document.getElementById('components-list');
    
    // Type de construction sélectionné
    buildTypeSelect.addEventListener('change', function() {
        // Réinitialiser les sélecteurs
        resetSelectors();
        
        if (this.value === 'building') {
            // Afficher le sélecteur de bâtiment
            buildingSelectGroup.classList.remove('d-none');
            buildingSelect.setAttribute('required', 'required');
            
            // Cacher le sélecteur de vaisseau
            shipSelectGroup.classList.add('d-none');
            shipSelect.removeAttribute('required');
            shipQuantityGroup.classList.add('d-none');
            quantityInput.removeAttribute('required');
            
            // Cacher la carte des composants
            shipComponentsCard.classList.add('d-none');
        } else if (this.value === 'ship') {
            // Afficher le sélecteur de vaisseau
            shipSelectGroup.classList.remove('d-none');
            shipSelect.setAttribute('required', 'required');
            shipQuantityGroup.classList.remove('d-none');
            quantityInput.setAttribute('required', 'required');
            
            // Cacher le sélecteur de bâtiment
            buildingSelectGroup.classList.add('d-none');
            buildingSelect.removeAttribute('required');
            buildingLevelGroup.classList.add('d-none');
            buildingLevelSelect.removeAttribute('required');
            
            // Afficher la carte des composants
            shipComponentsCard.classList.remove('d-none');
        } else {
            // Cacher tous les sélecteurs spécifiques
            buildingSelectGroup.classList.add('d-none');
            buildingSelect.removeAttribute('required');
            buildingLevelGroup.classList.add('d-none');
            buildingLevelSelect.removeAttribute('required');
            shipSelectGroup.classList.add('d-none');
            shipSelect.removeAttribute('required');
            shipQuantityGroup.classList.add('d-none');
            quantityInput.removeAttribute('required');
            
            // Cacher la carte des composants
            shipComponentsCard.classList.add('d-none');
        }
        
        // Cacher les détails
        showConstructionPlaceholder();
        showComponentsPlaceholder();
    });
    
    // Emplacement sélectionné
    locationSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            document.getElementById('construction-capacity').value = selectedOption.dataset.capacity;
            
            // Mettre à jour les détails de construction si un élément est sélectionné
            updateConstructionDetails();
        } else {
            document.getElementById('construction-capacity').value = '';
        }
    });
    
    // Bâtiment sélectionné
    buildingSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const maxLevel = parseInt(selectedOption.dataset.maxLevel);
            
            // Mettre à jour les niveaux disponibles
            buildingLevelSelect.innerHTML = '<option value="">Sélectionnez un niveau</option>';
            
            for (let level = 1; level <= maxLevel; level++) {
                const option = document.createElement('option');
                option.value = level;
                option.textContent = `Niveau ${level}`;
                buildingLevelSelect.appendChild(option);
            }
            
            // Afficher le sélecteur de niveau
            buildingLevelGroup.classList.remove('d-none');
            buildingLevelSelect.setAttribute('required', 'required');
            
            // Mettre à jour les détails de construction
            updateConstructionDetails();
        } else {
            // Cacher le sélecteur de niveau
            buildingLevelGroup.classList.add('d-none');
            buildingLevelSelect.removeAttribute('required');
            
            // Cacher les détails
            showConstructionPlaceholder();
        }
    });
    
    // Niveau de bâtiment sélectionné
    buildingLevelSelect.addEventListener('change', function() {
        updateConstructionDetails();
    });
    
    // Vaisseau sélectionné
    shipSelect.addEventListener('change', function() {
        if (this.value) {
            // Mettre à jour les détails de construction
            updateConstructionDetails();
            
            // Mettre à jour les composants du vaisseau
            updateShipComponents();
        } else {
            // Cacher les détails
            showConstructionPlaceholder();
            showComponentsPlaceholder();
        }
    });
    
    // Quantité de vaisseaux modifiée
    quantityInput.addEventListener('input', function() {
        updateConstructionDetails();
    });
    
    // Mettre à jour les détails de construction
    function updateConstructionDetails() {
        let selectedItem, cost, constructionTime, name, description, subtext, icon;
        
        // Obtenir les détails en fonction du type de construction
        if (buildTypeSelect.value === 'building' && buildingSelect.value) {
            selectedItem = buildingSelect.options[buildingSelect.selectedIndex];
            name = selectedItem.dataset.name;
            description = selectedItem.dataset.description;
            cost = JSON.parse(selectedItem.dataset.cost);
            constructionTime = parseInt(selectedItem.dataset.constructionTime);
            icon = selectedItem.dataset.icon || 'fa-building';
            
            // Ajouter le niveau si sélectionné
            if (buildingLevelSelect.value) {
                const level = parseInt(buildingLevelSelect.value);
                subtext = `Niveau ${level}`;
                
                // Ajuster le coût et le temps en fonction du niveau
                cost = {
                    minerals: Math.round(cost.minerals * Math.pow(1.5, level - 1)),
                    gas: Math.round(cost.gas * Math.pow(1.5, level - 1)),
                    credits: Math.round(cost.credits * Math.pow(1.5, level - 1))
                };
                constructionTime = Math.round(constructionTime * Math.pow(1.2, level - 1));
            } else {
                subtext = 'Bâtiment';
            }
        } else if (buildTypeSelect.value === 'ship' && shipSelect.value) {
            selectedItem = shipSelect.options[shipSelect.selectedIndex];
            name = selectedItem.dataset.name;
            description = selectedItem.dataset.description;
            cost = JSON.parse(selectedItem.dataset.cost);
            constructionTime = parseInt(selectedItem.dataset.constructionTime);
            subtext = `Classe: ${selectedItem.dataset.class}`;
            icon = 'fa-space-shuttle';
            
            // Ajuster le coût et le temps en fonction de la quantité
            const quantity = parseInt(quantityInput.value) || 1;
            cost = {
                minerals: cost.minerals * quantity,
                gas: cost.gas * quantity,
                credits: cost.credits * quantity
            };
            constructionTime = Math.round(constructionTime * Math.sqrt(quantity));
        } else {
            // Aucun élément sélectionné
            showConstructionPlaceholder();
            return;
        }
        
        // Mettre à jour les détails de construction
        document.getElementById('construction-name').textContent = name;
        document.getElementById('construction-subtext').textContent = subtext;
        document.getElementById('construction-description').textContent = description;
        document.getElementById('construction-time').value = constructionTime;
        document.getElementById('construction-icon').className = `fas ${icon}`;
        
        // Mettre à jour les coûts
        document.getElementById('cost-minerals').textContent = cost.minerals;
        document.getElementById('cost-gas').textContent = cost.gas;
        document.getElementById('cost-credits').textContent = cost.credits;
        
        // Mettre à jour les barres de progression des ressources
        if (locationSelect.value) {
            const locationOption = locationSelect.options[locationSelect.selectedIndex];
            const resources = JSON.parse(locationOption.dataset.resources);
            
            updateResourceProgress('minerals-progress', cost.minerals, resources.minerals);
            updateResourceProgress('gas-progress', cost.gas, resources.gas);
            updateResourceProgress('credits-progress', cost.credits, resources.credits);
        }
        
        // Afficher les détails
        showConstructionInfo();
    }
    
    // Mettre à jour les composants du vaisseau
    function updateShipComponents() {
        if (buildTypeSelect.value === 'ship' && shipSelect.value) {
            const selectedShip = shipSelect.options[shipSelect.selectedIndex];
            const components = JSON.parse(selectedShip.dataset.components);
            
            // Vider la liste des composants
            componentsList.innerHTML = '';
            
            // Ajouter chaque composant à la liste
            if (components && components.length > 0) {
                components.forEach(component => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item bg-dark text-light border-secondary';
                    
                    const componentName = document.createElement('div');
                    componentName.className = 'fw-bold';
                    componentName.textContent = component.name;
                    
                    const componentSpecs = document.createElement('div');
                    componentSpecs.className = 'small text-muted';
                    componentSpecs.textContent = component.specs;
                    
                    li.appendChild(componentName);
                    li.appendChild(componentSpecs);
                    componentsList.appendChild(li);
                });
                
                // Afficher la liste des composants
                showComponentsInfo();
            } else {
                // Aucun composant
                showComponentsPlaceholder();
            }
        } else {
            // Aucun vaisseau sélectionné
            showComponentsPlaceholder();
        }
    }
    
    // Mettre à jour la barre de progression d'une ressource
    function updateResourceProgress(barId, cost, available) {
        const bar = document.getElementById(barId);
        const percentage = Math.min(100, (available / cost) * 100);
        bar.style.width = `${percentage}%`;
        
        // Changer la couleur si les ressources sont insuffisantes
        if (available < cost) {
            bar.classList.remove('bg-info', 'bg-warning', 'bg-success');
            bar.classList.add('bg-danger');
        } else {
            // Restaurer la couleur d'origine
            if (barId === 'minerals-progress') {
                bar.classList.remove('bg-danger', 'bg-warning', 'bg-success');
                bar.classList.add('bg-info');
            } else if (barId === 'gas-progress') {
                bar.classList.remove('bg-danger', 'bg-info', 'bg-success');
                bar.classList.add('bg-warning');
            } else if (barId === 'credits-progress') {
                bar.classList.remove('bg-danger', 'bg-info', 'bg-warning');
                bar.classList.add('bg-success');
            }
        }
    }
    
    // Réinitialiser les sélecteurs
    function resetSelectors() {
        buildingSelect.selectedIndex = 0;
        buildingLevelSelect.innerHTML = '<option value="">Sélectionnez un niveau</option>';
        shipSelect.selectedIndex = 0;
        quantityInput.value = 1;
    }
    
    // Afficher le placeholder de construction
    function showConstructionPlaceholder() {
        constructionPlaceholder.classList.remove('d-none');
        constructionInfo.classList.add('d-none');
    }
    
    // Afficher les informations de construction
    function showConstructionInfo() {
        constructionPlaceholder.classList.add('d-none');
        constructionInfo.classList.remove('d-none');
    }
    
    // Afficher le placeholder des composants
    function showComponentsPlaceholder() {
        componentsPlaceholder.classList.remove('d-none');
        componentsInfo.classList.add('d-none');
    }
    
    // Afficher les informations des composants
    function showComponentsInfo() {
        componentsPlaceholder.classList.add('d-none');
        componentsInfo.classList.remove('d-none');
    }
    
    // Initialisation
    shipComponentsCard.classList.add('d-none');
});
</script>
@endsection
