@extends('game.orders.create.layout')

@php
$orderTypeTitle = 'Déplacement';
$orderType = 'move';
$orderTypeIcon = 'fa-route';
$instructions = 'Sélectionnez une flotte et un système de destination pour créer un ordre de déplacement. La flotte se déplacera vers le système cible au prochain tour.';
@endphp

@section('order_form')
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="fleet_id" class="form-label">Flotte à déplacer</label>
            <select class="form-select bg-dark text-light border-secondary" id="fleet_id" name="fleet_id" required>
                <option value="">Sélectionnez une flotte</option>
                @foreach($fleets as $fleet)
                <option value="{{ $fleet->id }}" data-system-id="{{ $fleet->star_system_id }}" data-system-name="{{ $fleet->starSystem->name }}">
                    {{ $fleet->name }} ({{ $fleet->ships_count }} vaisseaux)
                </option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner une flotte.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="origin_system" class="form-label">Système d'origine</label>
            <input type="text" class="form-control bg-dark text-light border-secondary" id="origin_system" readonly>
        </div>
        
        <div class="form-group mb-3">
            <label for="destination_system_id" class="form-label">Système de destination</label>
            <select class="form-select bg-dark text-light border-secondary" id="destination_system_id" name="destination_system_id" required>
                <option value="">Sélectionnez un système</option>
                @foreach($systems as $system)
                <option value="{{ $system->id }}" data-x="{{ $system->x }}" data-y="{{ $system->y }}" data-z="{{ $system->z }}">
                    {{ $system->name }} ({{ $system->planets_count }} planètes)
                </option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un système de destination.
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-body">
                <h6 class="card-title">Détails du déplacement</h6>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="distance" class="form-label">Distance</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="distance" readonly>
                                <span class="input-group-text bg-dark text-light border-secondary">parsecs</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="estimated_turns" class="form-label">Durée estimée</label>
                            <div class="input-group">
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="estimated_turns" readonly>
                                <span class="input-group-text bg-dark text-light border-secondary">tour(s)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="fuel_consumption" class="form-label">Consommation de carburant</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="fuel_consumption" readonly>
                        <span class="input-group-text bg-dark text-light border-secondary">unités</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="fuel_available" class="form-label">Carburant disponible</label>
                    <div class="input-group">
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="fuel_available" readonly>
                        <span class="input-group-text bg-dark text-light border-secondary">unités</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="moveMap" class="move-map" style="height: 300px; background-color: #1a1d20; border-radius: 0.25rem; position: relative;">
            <div class="text-center text-muted py-5">
                <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                <p>Sélectionnez une flotte et une destination pour afficher la carte</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('order_scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fleetSelect = document.getElementById('fleet_id');
    const originSystemInput = document.getElementById('origin_system');
    const destinationSelect = document.getElementById('destination_system_id');
    const distanceInput = document.getElementById('distance');
    const estimatedTurnsInput = document.getElementById('estimated_turns');
    const fuelConsumptionInput = document.getElementById('fuel_consumption');
    const fuelAvailableInput = document.getElementById('fuel_available');
    const moveMap = document.getElementById('moveMap');
    
    // Flotte sélectionnée
    fleetSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const systemName = selectedOption.dataset.systemName;
            originSystemInput.value = systemName;
            
            // Simuler la récupération des données de carburant
            fuelAvailableInput.value = '1000';
            
            updateMoveDetails();
        } else {
            originSystemInput.value = '';
            fuelAvailableInput.value = '';
            clearMoveDetails();
        }
    });
    
    // Destination sélectionnée
    destinationSelect.addEventListener('change', function() {
        updateMoveDetails();
    });
    
    // Mise à jour des détails du déplacement
    function updateMoveDetails() {
        const fleetId = fleetSelect.value;
        const destinationId = destinationSelect.value;
        
        if (fleetId && destinationId) {
            // Dans un cas réel, ces valeurs seraient calculées par une requête AJAX
            // Ici, nous simulons des valeurs pour la démonstration
            const distance = calculateDistance();
            const estimatedTurns = Math.ceil(distance / 10);
            const fuelConsumption = Math.ceil(distance * 5);
            
            distanceInput.value = distance.toFixed(2);
            estimatedTurnsInput.value = estimatedTurns;
            fuelConsumptionInput.value = fuelConsumption;
            
            // Vérifier si le carburant est suffisant
            const fuelAvailable = parseFloat(fuelAvailableInput.value);
            if (fuelConsumption > fuelAvailable) {
                fuelConsumptionInput.classList.add('is-invalid');
                fuelConsumptionInput.parentElement.insertAdjacentHTML('beforeend', 
                    '<div class="invalid-feedback">Carburant insuffisant pour ce déplacement.</div>');
            } else {
                fuelConsumptionInput.classList.remove('is-invalid');
                const feedback = fuelConsumptionInput.parentElement.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }
            
            updateMoveMap();
        } else {
            clearMoveDetails();
        }
    }
    
    // Calcul de la distance entre deux systèmes
    function calculateDistance() {
        const fleetOption = fleetSelect.options[fleetSelect.selectedIndex];
        const destinationOption = destinationSelect.options[destinationSelect.selectedIndex];
        
        if (!fleetOption || !destinationOption) {
            return 0;
        }
        
        // Simuler une distance aléatoire entre 5 et 30 parsecs
        return Math.random() * 25 + 5;
    }
    
    // Mise à jour de la carte de déplacement
    function updateMoveMap() {
        const fleetOption = fleetSelect.options[fleetSelect.selectedIndex];
        const destinationOption = destinationSelect.options[destinationSelect.selectedIndex];
        
        if (!fleetOption || !destinationOption) {
            return;
        }
        
        const originName = fleetOption.dataset.systemName;
        const destinationName = destinationOption.textContent.split('(')[0].trim();
        
        // Créer une visualisation simple de la route
        moveMap.innerHTML = `
            <div class="origin-system" style="position: absolute; left: 20%; top: 50%;">
                <div class="system-marker origin" style="width: 12px; height: 12px; background-color: #0d6efd; border-radius: 50%; margin-bottom: 5px;"></div>
                <div class="system-label" style="font-size: 12px; color: #adb5bd;">${originName}</div>
            </div>
            
            <div class="destination-system" style="position: absolute; left: 80%; top: 50%;">
                <div class="system-marker destination" style="width: 12px; height: 12px; background-color: #dc3545; border-radius: 50%; margin-bottom: 5px;"></div>
                <div class="system-label" style="font-size: 12px; color: #adb5bd;">${destinationName}</div>
            </div>
            
            <div class="route-line" style="
                position: absolute;
                left: 20%;
                top: 50%;
                width: 60%;
                height: 2px;
                background-color: #6c757d;
                transform-origin: left center;
            "></div>
            
            <div class="distance-label" style="
                position: absolute;
                left: 50%;
                top: 40%;
                transform: translateX(-50%);
                font-size: 12px;
                color: #adb5bd;
                background-color: rgba(26, 29, 32, 0.7);
                padding: 2px 8px;
                border-radius: 10px;
            ">
                ${distanceInput.value} parsecs
            </div>
        `;
    }
    
    // Effacer les détails du déplacement
    function clearMoveDetails() {
        distanceInput.value = '';
        estimatedTurnsInput.value = '';
        fuelConsumptionInput.value = '';
        
        moveMap.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                <p>Sélectionnez une flotte et une destination pour afficher la carte</p>
            </div>
        `;
    }
});
</script>
@endsection
