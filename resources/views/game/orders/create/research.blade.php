@extends('game.orders.create.layout')

@php
$orderTypeTitle = 'Recherche';
$orderType = 'research';
$orderTypeIcon = 'fa-flask';
$instructions = 'Sélectionnez une technologie à rechercher et le niveau cible. La recherche débutera au prochain tour et nécessitera des points de recherche.';
@endphp

@section('order_form')
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="technology_id" class="form-label">Technologie</label>
            <select class="form-select bg-dark text-light border-secondary" id="technology_id" name="technology_id" required>
                <option value="">Sélectionnez une technologie</option>
                @foreach($technologyCategories as $category => $technologies)
                <optgroup label="{{ $category }}">
                    @foreach($technologies as $tech)
                    <option value="{{ $tech->id }}" 
                            data-current-level="{{ $tech->current_level }}"
                            data-max-level="{{ $tech->max_level }}"
                            data-description="{{ $tech->description }}"
                            data-points-per-level="{{ $tech->points_per_level }}"
                            data-icon="{{ $tech->icon }}">
                        {{ $tech->name }} (Niveau {{ $tech->current_level }}/{{ $tech->max_level }})
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner une technologie.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="target_level" class="form-label">Niveau cible</label>
            <select class="form-select bg-dark text-light border-secondary" id="target_level" name="target_level" required disabled>
                <option value="">Sélectionnez d'abord une technologie</option>
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un niveau cible.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="research_center_id" class="form-label">Centre de recherche</label>
            <select class="form-select bg-dark text-light border-secondary" id="research_center_id" name="research_center_id" required>
                <option value="">Sélectionnez un centre de recherche</option>
                @foreach($researchCenters as $center)
                <option value="{{ $center->id }}" data-capacity="{{ $center->research_capacity }}" data-planet="{{ $center->planet_name }}">
                    {{ $center->name }} ({{ $center->planet_name }}) - Capacité: {{ $center->research_capacity }} pts/tour
                </option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un centre de recherche.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="priority" class="form-label">Priorité</label>
            <select class="form-select bg-dark text-light border-secondary" id="priority" name="priority" required>
                <option value="high">Haute - 100% des ressources</option>
                <option value="medium" selected>Moyenne - 75% des ressources</option>
                <option value="low">Basse - 50% des ressources</option>
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner une priorité.
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Détails de la technologie</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4" id="tech-placeholder">
                    <i class="fas fa-flask fa-3x mb-3"></i>
                    <p>Sélectionnez une technologie pour afficher ses détails</p>
                </div>
                
                <div id="tech-info" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <div class="tech-icon me-3">
                            <i class="fas fa-2x" id="tech-icon"></i>
                        </div>
                        <div>
                            <h5 id="tech-name" class="mb-0"></h5>
                            <div class="text-muted small" id="tech-level"></div>
                        </div>
                    </div>
                    
                    <div class="tech-description mb-3" id="tech-description"></div>
                    
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-info" id="tech-progress" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Points requis</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="required-points" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">pts</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Durée estimée</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="estimated-turns" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">tour(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-dark border-secondary">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Avantages débloqués</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4" id="benefits-placeholder">
                    <i class="fas fa-unlock fa-3x mb-3"></i>
                    <p>Sélectionnez une technologie et un niveau cible pour voir les avantages</p>
                </div>
                
                <div id="benefits-info" class="d-none">
                    <ul class="list-group list-group-flush bg-transparent" id="benefits-list">
                        <!-- Les avantages seront ajoutés ici dynamiquement -->
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
    const techSelect = document.getElementById('technology_id');
    const targetLevelSelect = document.getElementById('target_level');
    const researchCenterSelect = document.getElementById('research_center_id');
    const prioritySelect = document.getElementById('priority');
    
    const techPlaceholder = document.getElementById('tech-placeholder');
    const techInfo = document.getElementById('tech-info');
    const benefitsPlaceholder = document.getElementById('benefits-placeholder');
    const benefitsInfo = document.getElementById('benefits-info');
    const benefitsList = document.getElementById('benefits-list');
    
    // Technologie sélectionnée
    techSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            const currentLevel = parseInt(selectedOption.dataset.currentLevel);
            const maxLevel = parseInt(selectedOption.dataset.maxLevel);
            
            // Mettre à jour les niveaux disponibles
            targetLevelSelect.innerHTML = '<option value="">Sélectionnez un niveau</option>';
            
            for (let level = currentLevel + 1; level <= maxLevel; level++) {
                const option = document.createElement('option');
                option.value = level;
                option.textContent = `Niveau ${level}`;
                targetLevelSelect.appendChild(option);
            }
            
            targetLevelSelect.disabled = false;
            
            // Afficher les détails de la technologie
            document.getElementById('tech-name').textContent = selectedOption.textContent.split('(')[0].trim();
            document.getElementById('tech-level').textContent = `Niveau actuel: ${currentLevel}/${maxLevel}`;
            document.getElementById('tech-description').textContent = selectedOption.dataset.description;
            document.getElementById('tech-icon').className = `fas ${selectedOption.dataset.icon || 'fa-flask'}`;
            
            // Calculer la progression
            const progress = (currentLevel / maxLevel) * 100;
            document.getElementById('tech-progress').style.width = `${progress}%`;
            document.getElementById('tech-progress').setAttribute('aria-valuenow', progress);
            
            // Afficher les détails
            showTechInfo();
        } else {
            // Réinitialiser et désactiver le sélecteur de niveau
            targetLevelSelect.innerHTML = '<option value="">Sélectionnez d\'abord une technologie</option>';
            targetLevelSelect.disabled = true;
            
            // Cacher les détails
            showTechPlaceholder();
            showBenefitsPlaceholder();
        }
    });
    
    // Niveau cible sélectionné
    targetLevelSelect.addEventListener('change', function() {
        if (this.value && techSelect.value) {
            const techOption = techSelect.options[techSelect.selectedIndex];
            const currentLevel = parseInt(techOption.dataset.currentLevel);
            const targetLevel = parseInt(this.value);
            const pointsPerLevel = parseInt(techOption.dataset.pointsPerLevel);
            
            // Calculer les points requis
            const requiredPoints = calculateRequiredPoints(currentLevel, targetLevel, pointsPerLevel);
            document.getElementById('required-points').value = requiredPoints;
            
            // Mettre à jour la durée estimée si un centre de recherche est sélectionné
            updateEstimatedTurns();
            
            // Afficher les avantages débloqués
            updateBenefits(techOption.textContent.split('(')[0].trim(), targetLevel);
        } else {
            document.getElementById('required-points').value = '';
            document.getElementById('estimated-turns').value = '';
            showBenefitsPlaceholder();
        }
    });
    
    // Centre de recherche sélectionné
    researchCenterSelect.addEventListener('change', function() {
        updateEstimatedTurns();
    });
    
    // Priorité sélectionnée
    prioritySelect.addEventListener('change', function() {
        updateEstimatedTurns();
    });
    
    // Calculer les points requis pour la recherche
    function calculateRequiredPoints(currentLevel, targetLevel, pointsPerLevel) {
        let totalPoints = 0;
        for (let level = currentLevel + 1; level <= targetLevel; level++) {
            totalPoints += pointsPerLevel * level;
        }
        return totalPoints;
    }
    
    // Mettre à jour la durée estimée
    function updateEstimatedTurns() {
        const requiredPointsValue = document.getElementById('required-points').value;
        
        if (requiredPointsValue && researchCenterSelect.value) {
            const requiredPoints = parseInt(requiredPointsValue);
            const centerOption = researchCenterSelect.options[researchCenterSelect.selectedIndex];
            const capacity = parseInt(centerOption.dataset.capacity);
            
            // Appliquer le facteur de priorité
            let priorityFactor = 1.0;
            switch (prioritySelect.value) {
                case 'high':
                    priorityFactor = 1.0;
                    break;
                case 'medium':
                    priorityFactor = 0.75;
                    break;
                case 'low':
                    priorityFactor = 0.5;
                    break;
            }
            
            const effectiveCapacity = capacity * priorityFactor;
            const estimatedTurns = Math.ceil(requiredPoints / effectiveCapacity);
            
            document.getElementById('estimated-turns').value = estimatedTurns;
        } else {
            document.getElementById('estimated-turns').value = '';
        }
    }
    
    // Mettre à jour les avantages débloqués
    function updateBenefits(techName, targetLevel) {
        // Dans un cas réel, ces données viendraient d'une requête AJAX
        // Simuler des avantages en fonction de la technologie et du niveau
        
        benefitsList.innerHTML = '';
        
        // Générer des avantages fictifs
        const benefits = [];
        
        if (techName.includes('Propulsion')) {
            benefits.push(`Vitesse de déplacement +${targetLevel * 10}%`);
            benefits.push(`Consommation de carburant -${targetLevel * 5}%`);
            if (targetLevel >= 3) benefits.push('Débloque les moteurs à impulsion');
            if (targetLevel >= 5) benefits.push('Débloque les moteurs à distorsion');
        } else if (techName.includes('Armement')) {
            benefits.push(`Puissance d'attaque +${targetLevel * 15}%`);
            if (targetLevel >= 2) benefits.push('Débloque les canons laser');
            if (targetLevel >= 4) benefits.push('Débloque les missiles guidés');
            if (targetLevel >= 6) benefits.push('Débloque les canons à plasma');
        } else if (techName.includes('Bouclier')) {
            benefits.push(`Résistance aux dégâts +${targetLevel * 12}%`);
            if (targetLevel >= 3) benefits.push('Débloque les boucliers énergétiques');
            if (targetLevel >= 5) benefits.push('Débloque la régénération des boucliers');
        } else {
            benefits.push(`Bonus de niveau ${targetLevel}`);
            benefits.push(`Amélioration générale +${targetLevel * 8}%`);
        }
        
        // Ajouter les avantages à la liste
        benefits.forEach(benefit => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-dark text-light border-secondary';
            
            const icon = document.createElement('i');
            icon.className = 'fas fa-check-circle text-success me-2';
            
            li.appendChild(icon);
            li.appendChild(document.createTextNode(benefit));
            benefitsList.appendChild(li);
        });
        
        // Afficher la section des avantages
        showBenefitsInfo();
    }
    
    // Afficher le placeholder de la technologie
    function showTechPlaceholder() {
        techPlaceholder.classList.remove('d-none');
        techInfo.classList.add('d-none');
    }
    
    // Afficher les informations de la technologie
    function showTechInfo() {
        techPlaceholder.classList.add('d-none');
        techInfo.classList.remove('d-none');
    }
    
    // Afficher le placeholder des avantages
    function showBenefitsPlaceholder() {
        benefitsPlaceholder.classList.remove('d-none');
        benefitsInfo.classList.add('d-none');
    }
    
    // Afficher les informations des avantages
    function showBenefitsInfo() {
        benefitsPlaceholder.classList.add('d-none');
        benefitsInfo.classList.remove('d-none');
    }
});
</script>
@endsection
