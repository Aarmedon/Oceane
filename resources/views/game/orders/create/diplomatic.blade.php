@extends('game.orders.create.layout')

@php
$orderTypeTitle = 'Action Diplomatique';
$orderType = 'diplomatic';
$orderTypeIcon = 'fa-handshake';
$instructions = 'Sélectionnez un type d\'action diplomatique et un commandant cible. Les actions diplomatiques seront traitées au prochain tour.';
@endphp

@section('order_form')
<div class="row">
    <div class="col-md-6">
        <div class="form-group mb-3">
            <label for="diplomatic_type" class="form-label">Type d'action diplomatique</label>
            <select class="form-select bg-dark text-light border-secondary" id="diplomatic_type" name="diplomatic_type" required>
                <option value="">Sélectionnez un type d'action</option>
                <option value="alliance_proposal" data-icon="fa-handshake" data-color="success">Proposition d'alliance</option>
                <option value="peace_proposal" data-icon="fa-dove" data-color="info">Proposition de paix</option>
                <option value="trade_proposal" data-icon="fa-exchange-alt" data-color="warning">Proposition commerciale</option>
                <option value="war_declaration" data-icon="fa-gavel" data-color="danger">Déclaration de guerre</option>
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un type d'action diplomatique.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="target_commander_id" class="form-label">Commandant cible</label>
            <select class="form-select bg-dark text-light border-secondary" id="target_commander_id" name="target_commander_id" required>
                <option value="">Sélectionnez un commandant</option>
                @foreach($commanders as $commander)
                <option value="{{ $commander->id }}" 
                        data-name="{{ $commander->name }}"
                        data-faction="{{ $commander->faction }}"
                        data-relation="{{ $commander->relation }}"
                        data-planets="{{ $commander->planets_count }}"
                        data-fleets="{{ $commander->fleets_count }}"
                        data-avatar="{{ $commander->avatar }}">
                    {{ $commander->name }} ({{ $commander->faction }})
                </option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Veuillez sélectionner un commandant cible.
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="duration" class="form-label">Durée (tours)</label>
            <input type="number" class="form-control bg-dark text-light border-secondary" id="duration" name="duration" min="5" max="100" value="10" required>
            <div class="invalid-feedback">
                Veuillez entrer une durée valide (entre 5 et 100 tours).
            </div>
        </div>
        
        <!-- Champs spécifiques pour les propositions commerciales -->
        <div id="trade-options" class="d-none">
            <h6 class="mb-3">Termes de l'échange</h6>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="offer_resource" class="form-label">Ressource offerte</label>
                        <select class="form-select bg-dark text-light border-secondary" id="offer_resource" name="offer_resource">
                            <option value="minerals">Minéraux</option>
                            <option value="gas">Gaz</option>
                            <option value="credits">Crédits</option>
                            <option value="technology">Technologie</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="offer_amount" class="form-label">Quantité offerte</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="offer_amount" name="offer_amount" min="1" value="100">
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="request_resource" class="form-label">Ressource demandée</label>
                        <select class="form-select bg-dark text-light border-secondary" id="request_resource" name="request_resource">
                            <option value="minerals">Minéraux</option>
                            <option value="gas" selected>Gaz</option>
                            <option value="credits">Crédits</option>
                            <option value="technology">Technologie</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="request_amount" class="form-label">Quantité demandée</label>
                        <input type="number" class="form-control bg-dark text-light border-secondary" id="request_amount" name="request_amount" min="1" value="100">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group mb-3">
            <label for="message" class="form-label">Message diplomatique</label>
            <textarea class="form-control bg-dark text-light border-secondary" id="message" name="message" rows="3" placeholder="Entrez un message à l'attention du commandant cible..."></textarea>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-dark border-secondary mb-3">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Détails de l'action</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4" id="action-placeholder">
                    <i class="fas fa-handshake fa-3x mb-3"></i>
                    <p>Sélectionnez un type d'action et un commandant cible pour afficher les détails</p>
                </div>
                
                <div id="action-info" class="d-none">
                    <div class="d-flex align-items-center mb-3">
                        <div class="action-icon me-3">
                            <i class="fas fa-2x" id="action-icon"></i>
                        </div>
                        <div>
                            <h5 id="action-name" class="mb-0"></h5>
                            <div class="text-muted small" id="action-subtext"></div>
                        </div>
                    </div>
                    
                    <div class="alert" id="action-alert" role="alert">
                        <!-- Le contenu de l'alerte sera défini dynamiquement -->
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Coût diplomatique</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="diplomatic-cost" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">points</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Chance de succès</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-dark text-light border-secondary" id="success-chance" readonly>
                                    <span class="input-group-text bg-dark text-light border-secondary">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Conséquences potentielles</label>
                        <ul class="list-group list-group-flush bg-transparent" id="consequences-list">
                            <!-- Les conséquences seront ajoutées ici dynamiquement -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card bg-dark border-secondary">
            <div class="card-header bg-dark border-secondary">
                <h6 class="mb-0">Profil du commandant cible</h6>
            </div>
            <div class="card-body">
                <div class="text-center text-muted py-4" id="commander-placeholder">
                    <i class="fas fa-user-astronaut fa-3x mb-3"></i>
                    <p>Sélectionnez un commandant cible pour afficher son profil</p>
                </div>
                
                <div id="commander-info" class="d-none">
                    <div class="d-flex mb-3">
                        <div class="commander-avatar me-3">
                            <img src="" id="commander-avatar" alt="Avatar du commandant" class="rounded-circle" style="width: 64px; height: 64px;">
                        </div>
                        <div>
                            <h5 id="commander-name" class="mb-0"></h5>
                            <div class="text-muted small" id="commander-faction"></div>
                            <div class="mt-1">
                                <span class="badge" id="relation-badge"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Planètes</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="commander-planets" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Flottes</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="commander-fleets" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Historique diplomatique</label>
                        <div class="diplomatic-history small" id="diplomatic-history">
                            <!-- L'historique sera ajouté ici dynamiquement -->
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
    const diplomaticTypeSelect = document.getElementById('diplomatic_type');
    const targetCommanderSelect = document.getElementById('target_commander_id');
    const durationInput = document.getElementById('duration');
    const tradeOptions = document.getElementById('trade-options');
    
    const actionPlaceholder = document.getElementById('action-placeholder');
    const actionInfo = document.getElementById('action-info');
    const commanderPlaceholder = document.getElementById('commander-placeholder');
    const commanderInfo = document.getElementById('commander-info');
    
    // Type d'action diplomatique sélectionné
    diplomaticTypeSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            
            // Afficher ou cacher les options commerciales
            if (this.value === 'trade_proposal') {
                tradeOptions.classList.remove('d-none');
            } else {
                tradeOptions.classList.add('d-none');
            }
            
            // Mettre à jour les détails de l'action
            updateActionDetails();
        } else {
            tradeOptions.classList.add('d-none');
            showActionPlaceholder();
        }
    });
    
    // Commandant cible sélectionné
    targetCommanderSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.options[this.selectedIndex];
            
            // Mettre à jour le profil du commandant
            document.getElementById('commander-name').textContent = selectedOption.dataset.name;
            document.getElementById('commander-faction').textContent = selectedOption.dataset.faction;
            document.getElementById('commander-planets').value = selectedOption.dataset.planets;
            document.getElementById('commander-fleets').value = selectedOption.dataset.fleets;
            
            // Mettre à jour l'avatar (dans un cas réel, ce serait une URL d'image)
            document.getElementById('commander-avatar').src = selectedOption.dataset.avatar || 'https://via.placeholder.com/64';
            
            // Mettre à jour le badge de relation
            updateRelationBadge(selectedOption.dataset.relation);
            
            // Générer un historique diplomatique fictif
            generateDiplomaticHistory(selectedOption.dataset.name);
            
            // Afficher le profil du commandant
            showCommanderInfo();
            
            // Mettre à jour les détails de l'action
            updateActionDetails();
        } else {
            showCommanderPlaceholder();
        }
    });
    
    // Durée modifiée
    durationInput.addEventListener('input', function() {
        updateActionDetails();
    });
    
    // Mettre à jour les détails de l'action diplomatique
    function updateActionDetails() {
        if (!diplomaticTypeSelect.value || !targetCommanderSelect.value) {
            showActionPlaceholder();
            return;
        }
        
        const actionType = diplomaticTypeSelect.options[diplomaticTypeSelect.selectedIndex];
        const commander = targetCommanderSelect.options[targetCommanderSelect.selectedIndex];
        const duration = parseInt(durationInput.value) || 10;
        
        // Mettre à jour l'icône et le nom de l'action
        document.getElementById('action-icon').className = `fas ${actionType.dataset.icon}`;
        document.getElementById('action-name').textContent = actionType.textContent;
        document.getElementById('action-subtext').textContent = `Envers ${commander.dataset.name} pour ${duration} tours`;
        
        // Définir le contenu de l'alerte en fonction du type d'action
        const alertElement = document.getElementById('action-alert');
        alertElement.className = `alert alert-${actionType.dataset.color}`;
        
        switch (diplomaticTypeSelect.value) {
            case 'alliance_proposal':
                alertElement.innerHTML = `<i class="fas fa-info-circle me-2"></i> Une alliance avec ${commander.dataset.name} vous permettra de partager des technologies et de coordonner vos flottes.`;
                break;
            case 'peace_proposal':
                alertElement.innerHTML = `<i class="fas fa-info-circle me-2"></i> Un traité de paix avec ${commander.dataset.name} mettra fin aux hostilités et établira une zone démilitarisée.`;
                break;
            case 'trade_proposal':
                alertElement.innerHTML = `<i class="fas fa-info-circle me-2"></i> Un accord commercial avec ${commander.dataset.name} permettra l'échange régulier de ressources pendant la durée du traité.`;
                break;
            case 'war_declaration':
                alertElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i> Attention! Une déclaration de guerre envers ${commander.dataset.name} aura des conséquences diplomatiques avec les autres factions.`;
                break;
        }
        
        // Calculer le coût diplomatique et la chance de succès
        let diplomaticCost, successChance;
        const relation = commander.dataset.relation;
        
        switch (diplomaticTypeSelect.value) {
            case 'alliance_proposal':
                diplomaticCost = 100;
                successChance = relation === 'friendly' ? 80 : relation === 'neutral' ? 40 : 10;
                break;
            case 'peace_proposal':
                diplomaticCost = 50;
                successChance = relation === 'hostile' ? 30 : relation === 'at_war' ? 20 : 70;
                break;
            case 'trade_proposal':
                diplomaticCost = 20;
                successChance = relation === 'friendly' ? 90 : relation === 'neutral' ? 60 : 20;
                break;
            case 'war_declaration':
                diplomaticCost = 200;
                successChance = 100; // Une déclaration de guerre réussit toujours
                break;
            default:
                diplomaticCost = 50;
                successChance = 50;
        }
        
        // Ajuster en fonction de la durée
        diplomaticCost = Math.round(diplomaticCost * (duration / 10));
        
        document.getElementById('diplomatic-cost').value = diplomaticCost;
        document.getElementById('success-chance').value = successChance;
        
        // Générer les conséquences potentielles
        generateConsequences(diplomaticTypeSelect.value, commander.dataset.name, relation);
        
        // Afficher les détails de l'action
        showActionInfo();
    }
    
    // Mettre à jour le badge de relation
    function updateRelationBadge(relation) {
        const badge = document.getElementById('relation-badge');
        
        switch (relation) {
            case 'friendly':
                badge.className = 'badge bg-success';
                badge.textContent = 'Amical';
                break;
            case 'neutral':
                badge.className = 'badge bg-info';
                badge.textContent = 'Neutre';
                break;
            case 'hostile':
                badge.className = 'badge bg-warning text-dark';
                badge.textContent = 'Hostile';
                break;
            case 'at_war':
                badge.className = 'badge bg-danger';
                badge.textContent = 'En guerre';
                break;
            default:
                badge.className = 'badge bg-secondary';
                badge.textContent = 'Inconnu';
        }
    }
    
    // Générer l'historique diplomatique
    function generateDiplomaticHistory(commanderName) {
        const historyElement = document.getElementById('diplomatic-history');
        
        // Dans un cas réel, ces données viendraient d'une requête AJAX
        // Simuler un historique diplomatique
        const currentDate = new Date();
        const events = [
            {
                date: new Date(currentDate.getTime() - 30 * 24 * 60 * 60 * 1000), // 30 jours avant
                type: 'trade_proposal',
                description: `Accord commercial établi avec ${commanderName}`,
                status: 'accepted'
            },
            {
                date: new Date(currentDate.getTime() - 60 * 24 * 60 * 60 * 1000), // 60 jours avant
                type: 'peace_proposal',
                description: `Proposition de paix envoyée à ${commanderName}`,
                status: 'rejected'
            },
            {
                date: new Date(currentDate.getTime() - 90 * 24 * 60 * 60 * 1000), // 90 jours avant
                type: 'war_declaration',
                description: `Déclaration de guerre contre ${commanderName}`,
                status: 'executed'
            }
        ];
        
        // Vider l'historique
        historyElement.innerHTML = '';
        
        // Créer la timeline
        const timeline = document.createElement('div');
        timeline.className = 'timeline';
        
        events.forEach(event => {
            const item = document.createElement('div');
            item.className = 'timeline-item';
            
            const date = document.createElement('div');
            date.className = 'timeline-date';
            date.textContent = event.date.toLocaleDateString();
            
            const content = document.createElement('div');
            content.className = 'timeline-content';
            
            let icon, statusClass;
            switch (event.type) {
                case 'alliance_proposal':
                    icon = 'fa-handshake';
                    break;
                case 'peace_proposal':
                    icon = 'fa-dove';
                    break;
                case 'trade_proposal':
                    icon = 'fa-exchange-alt';
                    break;
                case 'war_declaration':
                    icon = 'fa-gavel';
                    break;
                default:
                    icon = 'fa-comment-dots';
            }
            
            switch (event.status) {
                case 'accepted':
                    statusClass = 'text-success';
                    break;
                case 'rejected':
                    statusClass = 'text-danger';
                    break;
                case 'pending':
                    statusClass = 'text-warning';
                    break;
                case 'executed':
                    statusClass = 'text-info';
                    break;
                default:
                    statusClass = 'text-muted';
            }
            
            content.innerHTML = `<i class="fas ${icon} me-2 ${statusClass}"></i> ${event.description}`;
            
            item.appendChild(date);
            item.appendChild(content);
            timeline.appendChild(item);
        });
        
        historyElement.appendChild(timeline);
    }
    
    // Générer les conséquences potentielles
    function generateConsequences(actionType, commanderName, relation) {
        const consequencesList = document.getElementById('consequences-list');
        
        // Vider la liste
        consequencesList.innerHTML = '';
        
        // Générer des conséquences en fonction du type d'action
        const consequences = [];
        
        switch (actionType) {
            case 'alliance_proposal':
                consequences.push({
                    text: `Accès aux technologies partagées avec ${commanderName}`,
                    icon: 'fa-flask',
                    color: 'text-success'
                });
                consequences.push({
                    text: 'Coordination des flottes pour la défense mutuelle',
                    icon: 'fa-shield-alt',
                    color: 'text-success'
                });
                consequences.push({
                    text: 'Relations diplomatiques améliorées avec les alliés de ' + commanderName,
                    icon: 'fa-users',
                    color: 'text-success'
                });
                if (relation === 'hostile' || relation === 'at_war') {
                    consequences.push({
                        text: 'Faible chance d\'acceptation en raison des relations tendues',
                        icon: 'fa-exclamation-triangle',
                        color: 'text-danger'
                    });
                }
                break;
                
            case 'peace_proposal':
                consequences.push({
                    text: 'Fin des hostilités avec ' + commanderName,
                    icon: 'fa-dove',
                    color: 'text-success'
                });
                consequences.push({
                    text: 'Établissement d\'une zone démilitarisée',
                    icon: 'fa-map-marked-alt',
                    color: 'text-info'
                });
                consequences.push({
                    text: 'Possibilité de futurs accords commerciaux',
                    icon: 'fa-exchange-alt',
                    color: 'text-success'
                });
                if (relation === 'at_war') {
                    consequences.push({
                        text: 'Réparations de guerre potentielles',
                        icon: 'fa-coins',
                        color: 'text-warning'
                    });
                }
                break;
                
            case 'trade_proposal':
                consequences.push({
                    text: 'Échange régulier de ressources avec ' + commanderName,
                    icon: 'fa-exchange-alt',
                    color: 'text-success'
                });
                consequences.push({
                    text: 'Amélioration des relations diplomatiques',
                    icon: 'fa-chart-line',
                    color: 'text-success'
                });
                if (relation === 'hostile' || relation === 'at_war') {
                    consequences.push({
                        text: 'Risque d\'espionnage ou de sabotage',
                        icon: 'fa-user-secret',
                        color: 'text-danger'
                    });
                }
                break;
                
            case 'war_declaration':
                consequences.push({
                    text: 'Hostilités ouvertes avec ' + commanderName,
                    icon: 'fa-fighter-jet',
                    color: 'text-danger'
                });
                consequences.push({
                    text: 'Relations diplomatiques détériorées avec les alliés de ' + commanderName,
                    icon: 'fa-users-slash',
                    color: 'text-danger'
                });
                consequences.push({
                    text: 'Possibilité de conquête de planètes ennemies',
                    icon: 'fa-globe',
                    color: 'text-warning'
                });
                consequences.push({
                    text: 'Risque de représailles et de pertes militaires',
                    icon: 'fa-skull-crossbones',
                    color: 'text-danger'
                });
                break;
        }
        
        // Ajouter les conséquences à la liste
        consequences.forEach(consequence => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-dark text-light border-secondary';
            
            const icon = document.createElement('i');
            icon.className = `fas ${consequence.icon} me-2 ${consequence.color}`;
            
            li.appendChild(icon);
            li.appendChild(document.createTextNode(consequence.text));
            consequencesList.appendChild(li);
        });
    }
    
    // Afficher le placeholder de l'action
    function showActionPlaceholder() {
        actionPlaceholder.classList.remove('d-none');
        actionInfo.classList.add('d-none');
    }
    
    // Afficher les informations de l'action
    function showActionInfo() {
        actionPlaceholder.classList.add('d-none');
        actionInfo.classList.remove('d-none');
    }
    
    // Afficher le placeholder du commandant
    function showCommanderPlaceholder() {
        commanderPlaceholder.classList.remove('d-none');
        commanderInfo.classList.add('d-none');
    }
    
    // Afficher les informations du commandant
    function showCommanderInfo() {
        commanderPlaceholder.classList.add('d-none');
        commanderInfo.classList.remove('d-none');
    }
});
</script>

<style>
/* Style pour la timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #495057;
}

.timeline-item {
    position: relative;
    margin-bottom: 15px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-date {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 2px;
}

.timeline-content {
    padding-left: 5px;
}

.timeline-item:before {
    content: '';
    position: absolute;
    left: -30px;
    top: 5px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #495057;
}
</style>
@endsection
