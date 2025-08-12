@extends('game.orders.create.layout')

@php
$orderTypeTitle = 'Colonisation';
$orderType = 'colonize';
$orderTypeIcon = 'fa-flag';
$instructions = 'Sélectionnez une planète non colonisée et un vaisseau colonisateur pour établir une nouvelle colonie. La colonisation débutera au prochain tour.';
@endphp

@section('order_form')
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="system_id" class="form-label">Système stellaire</label>
            <select class="form-select bg-dark text-light border-secondary" id="system_id" name="system_id" required>
                <option value="">Sélectionnez un système</option>
                @foreach($systems as $system)
                <option value="{{ $system->id }}">
                    {{ $system->name }} ({{ $system->uncolonized_planets_count }} planètes disponibles)
                </option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un système stellaire.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="planet_id" class="form-label">Planète à coloniser</label>
            <select class="form-select bg-dark text-light border-secondary" id="planet_id" name="planet_id" required disabled>
                <option value="">Sélectionnez d'abord un système</option>
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner une planète.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="ship_id" class="form-label">Vaisseau colonisateur</label>
            <select class="form-select bg-dark text-light border-secondary" id="ship_id" name="ship_id" required disabled>
                <option value="">Sélectionnez d'abord une planète</option>
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un vaisseau colonisateur.
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Détails de la planète</h6>
            </div>
            <div class="card-body planet-details">
                <div class="text-center text-muted py-4" id="planet-placeholder">
                    <i class="fas fa-globe fa-3x mb-3"></i>
                    <p>Sélectionnez une planète pour afficher ses détails</p>
                </div>
                
                <div id="planet-info" class="d-none">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Taille</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="planet-size" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Type</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="planet-type" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Habitabilité</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="planet-habitability" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Gravité</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="planet-gravity" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">G</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Ressources</label>
                        <div class="row">
                            <div class="col-4">
                                <div class="resource-indicator">
                                    <span class="resource-label">Minéraux</span>
                                    <div class="resource-bar" id="minerals-bar">
                                        <div class="resource-value" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="resource-indicator">
                                    <span class="resource-label">Gaz</span>
                                    <div class="resource-bar" id="gas-bar">
                                        <div class="resource-value" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="resource-indicator">
                                    <span class="resource-label">Énergie</span>
                                    <div class="resource-bar" id="energy-bar">
                                        <div class="resource-value" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Temps d'établissement estimé</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-dark text-light border-secondary" id="establishment-time" readonly>
                            <span class="input-group-text bg-dark text-light border-secondary">tour(s)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-dark border-secondary">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Détails du vaisseau colonisateur</h6>
            </div>
            <div class="card-body ship-details">
                <div class="text-center text-muted py-4" id="ship-placeholder">
                    <i class="fas fa-rocket fa-3x mb-3"></i>
                    <p>Sélectionnez un vaisseau pour afficher ses détails</p>
                </div>
                
                <div id="ship-info" class="d-none">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Classe</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="ship-class" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">État</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="ship-condition" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">Capacité de colonisation</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-dark text-light border-secondary" id="colonization-capacity" readonly>
                            <span class="input-group-text bg-dark text-light border-secondary">points</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Équipage</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-dark text-light border-secondary" id="ship-crew" readonly>
                            <span class="input-group-text bg-dark text-light border-secondary">personnes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('order_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const systemSelect = document.getElementById('system_id');
    const planetSelect = document.getElementById('planet_id');
    const shipSelect = document.getElementById('ship_id');
    
    const planetPlaceholder = document.getElementById('planet-placeholder');
    const planetInfo = document.getElementById('planet-info');
    const shipPlaceholder = document.getElementById('ship-placeholder');
    const shipInfo = document.getElementById('ship-info');
    
    // Système sélectionné
    systemSelect.addEventListener('change', function() {
        if (this.value) {
            // Activer le sélecteur de planètes
            planetSelect.disabled = false;
            
            // Dans un cas réel, ces données viendraient d'une requête AJAX
            // Simuler le chargement des planètes du système
            planetSelect.innerHTML = '<option value="">Sélectionnez une planète</option>';
            
            // Ajouter des planètes fictives
            const planetTypes = ['Tellurique', 'Gazeuse', 'Océanique', 'Désertique', 'Volcanique'];
            const planetCount = Math.floor(Math.random() * 5) + 1;
            
            for (let i = 1; i <= planetCount; i++) {
                const planetType = planetTypes[Math.floor(Math.random() * planetTypes.length)];
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `Planète ${i} (${planetType})`;
                option.dataset.type = planetType;
                option.dataset.size = Math.floor(Math.random() * 5) + 1;
                option.dataset.habitability = Math.floor(Math.random() * 100);
                option.dataset.gravity = (Math.random() * 2 + 0.5).toFixed(2);
                option.dataset.minerals = Math.floor(Math.random() * 100);
                option.dataset.gas = Math.floor(Math.random() * 100);
                option.dataset.energy = Math.floor(Math.random() * 100);
                
                planetSelect.appendChild(option);
            }
        } else {
            // Réinitialiser et désactiver les sélecteurs
            planetSelect.innerHTML = '<option value="">Sélectionnez d\'abord un système</option>';
            planetSelect.disabled = true;
            
            shipSelect.innerHTML = '<option value="">Sélectionnez d\'abord une planète</option>';
            shipSelect.disabled = true;
            
            // Cacher les détails
            showPlanetPlaceholder();
            showShipPlaceholder();
        }
    });
    
    // Planète sélectionnée
    planetSelect.addEventListener('change', function() {
        if (this.value) {
            // Afficher les détails de la planète
            const selectedOption = this.options[this.selectedIndex];
            
            document.getElementById('planet-type').value = selectedOption.dataset.type;
            document.getElementById('planet-size').value = `Classe ${selectedOption.dataset.size}`;
            document.getElementById('planet-habitability').value = selectedOption.dataset.habitability;
            document.getElementById('planet-gravity').value = selectedOption.dataset.gravity;
            
            // Mettre à jour les barres de ressources
            updateResourceBar('minerals-bar', selectedOption.dataset.minerals);
            updateResourceBar('gas-bar', selectedOption.dataset.gas);
            updateResourceBar('energy-bar', selectedOption.dataset.energy);
            
            // Calculer le temps d'établissement en fonction de l'habitabilité
            const habitability = parseInt(selectedOption.dataset.habitability);
            const establishmentTime = Math.max(1, Math.ceil((100 - habitability) / 20));
            document.getElementById('establishment-time').value = establishmentTime;
            
            // Afficher les détails de la planète
            showPlanetInfo();
            
            // Activer le sélecteur de vaisseaux
            shipSelect.disabled = false;
            
            // Dans un cas réel, ces données viendraient d'une requête AJAX
            // Simuler le chargement des vaisseaux colonisateurs disponibles
            shipSelect.innerHTML = '<option value="">Sélectionnez un vaisseau colonisateur</option>';
            
            // Ajouter des vaisseaux fictifs
            const shipClasses = ['Colonisateur léger', 'Colonisateur standard', 'Colonisateur lourd'];
            const shipCount = Math.floor(Math.random() * 3) + 1;
            
            for (let i = 1; i <= shipCount; i++) {
                const shipClass = shipClasses[Math.floor(Math.random() * shipClasses.length)];
                const option = document.createElement('option');
                option.value = i;
                option.textContent = `${shipClass} ${i}`;
                option.dataset.class = shipClass;
                option.dataset.condition = Math.floor(Math.random() * 30) + 70;
                option.dataset.capacity = Math.floor(Math.random() * 50) + 50;
                option.dataset.crew = Math.floor(Math.random() * 500) + 500;
                
                shipSelect.appendChild(option);
            }
        } else {
            // Réinitialiser et désactiver le sélecteur de vaisseaux
            shipSelect.innerHTML = '<option value="">Sélectionnez d\'abord une planète</option>';
            shipSelect.disabled = true;
            
            // Cacher les détails
            showPlanetPlaceholder();
            showShipPlaceholder();
        }
    });
    
    // Vaisseau sélectionné
    shipSelect.addEventListener('change', function() {
        if (this.value) {
            // Afficher les détails du vaisseau
            const selectedOption = this.options[this.selectedIndex];
            
            document.getElementById('ship-class').value = selectedOption.dataset.class;
            document.getElementById('ship-condition').value = `${selectedOption.dataset.condition}%`;
            document.getElementById('colonization-capacity').value = selectedOption.dataset.capacity;
            document.getElementById('ship-crew').value = selectedOption.dataset.crew;
            
            // Afficher les détails du vaisseau
            showShipInfo();
        } else {
            // Cacher les détails du vaisseau
            showShipPlaceholder();
        }
    });
    
    // Mettre à jour une barre de ressource
    function updateResourceBar(barId, value) {
        const bar = document.getElementById(barId);
        const valueElement = bar.querySelector('.resource-value');
        valueElement.style.width = `${value}%`;
        
        // Définir la couleur en fonction de la valeur
        if (value < 30) {
            valueElement.style.backgroundColor = '#dc3545'; // Rouge
        } else if (value < 70) {
            valueElement.style.backgroundColor = '#ffc107'; // Jaune
        } else {
            valueElement.style.backgroundColor = '#198754'; // Vert
        }
    }
    
    // Afficher le placeholder de la planète
    function showPlanetPlaceholder() {
        planetPlaceholder.classList.remove('d-none');
        planetInfo.classList.add('d-none');
    }
    
    // Afficher les informations de la planète
    function showPlanetInfo() {
        planetPlaceholder.classList.add('d-none');
        planetInfo.classList.remove('d-none');
    }
    
    // Afficher le placeholder du vaisseau
    function showShipPlaceholder() {
        shipPlaceholder.classList.remove('d-none');
        shipInfo.classList.add('d-none');
    }
    
    // Afficher les informations du vaisseau
    function showShipInfo() {
        shipPlaceholder.classList.add('d-none');
        shipInfo.classList.remove('d-none');
    }
});
</script>
@endsection
