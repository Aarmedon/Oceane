document.addEventListener('DOMContentLoaded', function() {
    // Récupération des données depuis la vue
    const ordersData = JSON.parse(document.getElementById('orders-data')?.textContent || '{"orders":{"pending":[],"processed":[],"failed":[]},"current_turn":1}');
    
    // Initialisation des tooltips Bootstrap
    initTooltips();
    
    // Configuration des événements pour les détails d'ordre
    setupOrderDetailsEvents();
    
    // Configuration des onglets
    setupTabsEvents();
    
    // Configuration des formulaires
    setupFormEvents();
});

/**
 * Initialise les tooltips Bootstrap
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Configure les événements pour les détails d'ordre
 */
function setupOrderDetailsEvents() {
    // Boutons de détails d'ordre
    const orderDetailsBtns = document.querySelectorAll('.order-details-btn');
    
    orderDetailsBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            showOrderDetails(orderId);
        });
    });
    
    // Bouton d'annulation d'ordre dans le modal
    const cancelOrderButton = document.getElementById('cancelOrderButton');
    if (cancelOrderButton) {
        cancelOrderButton.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            if (orderId) {
                if (confirm('Êtes-vous sûr de vouloir annuler cet ordre ?')) {
                    // Soumettre le formulaire d'annulation
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/game/orders/${orderId}/cancel`;
                    
                    // Ajouter le token CSRF
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken;
                    form.appendChild(csrfInput);
                    
                    // Ajouter la méthode DELETE
                    const methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);
                    
                    // Soumettre le formulaire
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    }
}

/**
 * Affiche les détails d'un ordre
 */
function showOrderDetails(orderId) {
    const modal = document.getElementById('orderDetailsModal');
    const modalTitle = document.getElementById('orderDetailsTitle');
    const modalContent = document.getElementById('orderDetailsContent');
    const cancelButton = document.getElementById('cancelOrderButton');
    
    // Afficher le modal
    const orderModal = new bootstrap.Modal(modal);
    orderModal.show();
    
    // Charger les détails de l'ordre
    fetch(`/game/orders/${orderId}/details`)
        .then(response => response.json())
        .then(data => {
            // Mettre à jour le titre du modal
            modalTitle.textContent = getOrderTypeLabel(data.order_type);
            
            // Construire le contenu du modal
            let content = `
                <div class="order-details mb-4">
                    <h6 class="order-details-title">Informations générales</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Type:</strong> ${getOrderTypeLabel(data.order_type)}</p>
                            <p><strong>Soumis au tour:</strong> ${data.turn_submitted}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Statut:</strong> ${getOrderStatusLabel(data.is_processed, data.processing_error)}</p>
                            <p><strong>Exécution prévue:</strong> Tour ${data.turn_execution}</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Ajouter les détails spécifiques selon le type d'ordre
            switch (data.order_type) {
                case 'move':
                    content += buildMoveOrderDetails(data);
                    break;
                case 'colonize':
                    content += buildColonizeOrderDetails(data);
                    break;
                case 'research':
                    content += buildResearchOrderDetails(data);
                    break;
                case 'build':
                    content += buildBuildOrderDetails(data);
                    break;
                case 'diplomatic':
                    content += buildDiplomaticOrderDetails(data);
                    break;
                default:
                    content += `<div class="alert alert-info">Détails non disponibles pour ce type d'ordre.</div>`;
            }
            
            // Ajouter un message d'erreur si l'ordre a échoué
            if (data.processing_error) {
                content += `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> Erreur lors du traitement</h6>
                        <p>${data.processing_error}</p>
                    </div>
                `;
            }
            
            modalContent.innerHTML = content;
            
            // Configurer le bouton d'annulation
            if (!data.is_processed && !data.processing_error) {
                cancelButton.style.display = '';
                cancelButton.setAttribute('data-order-id', data.id);
            } else {
                cancelButton.style.display = 'none';
                cancelButton.removeAttribute('data-order-id');
            }
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement des détails de l'ordre.
                </div>
            `;
            console.error('Erreur lors du chargement des détails:', error);
        });
}

/**
 * Construit les détails d'un ordre de déplacement
 */
function buildMoveOrderDetails(data) {
    const params = data.parameters;
    
    return `
        <div class="order-details">
            <h6 class="order-details-title">Détails du déplacement</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Flotte:</strong> ${params.fleet_name}</p>
                    <p><strong>Origine:</strong> ${params.origin_system_name}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Destination:</strong> ${params.destination_system_name}</p>
                    <p><strong>Distance:</strong> ${params.distance} parsecs</p>
                </div>
            </div>
            
            <div class="mt-3">
                <p><strong>Durée estimée:</strong> ${params.estimated_turns} tour(s)</p>
                <p><strong>Consommation de carburant:</strong> ${params.fuel_consumption} unités</p>
            </div>
            
            <div class="mt-3">
                <div id="moveMap" class="move-map" style="height: 300px; background-color: #1a1d20; border-radius: 0.25rem; position: relative;">
                    <!-- La carte sera générée par JavaScript -->
                    <div class="origin-system" style="position: absolute; left: ${params.map_coords.origin.x}%; top: ${params.map_coords.origin.y}%;">
                        <div class="system-marker origin"></div>
                        <div class="system-label">${params.origin_system_name}</div>
                    </div>
                    
                    <div class="destination-system" style="position: absolute; left: ${params.map_coords.destination.x}%; top: ${params.map_coords.destination.y}%;">
                        <div class="system-marker destination"></div>
                        <div class="system-label">${params.destination_system_name}</div>
                    </div>
                    
                    <div class="route-line" style="
                        position: absolute;
                        left: ${params.map_coords.origin.x}%;
                        top: ${params.map_coords.origin.y}%;
                        width: ${params.map_coords.route_length}px;
                        transform: rotate(${params.map_coords.route_angle}deg);
                        transform-origin: left center;
                    "></div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Construit les détails d'un ordre de colonisation
 */
function buildColonizeOrderDetails(data) {
    const params = data.parameters;
    
    return `
        <div class="order-details">
            <h6 class="order-details-title">Détails de la colonisation</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Vaisseau colonisateur:</strong> ${params.ship_name}</p>
                    <p><strong>Système:</strong> ${params.system_name}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Planète cible:</strong> ${params.planet_name}</p>
                    <p><strong>Type:</strong> ${params.planet_type}</p>
                </div>
            </div>
            
            <div class="mt-3">
                <h6>Caractéristiques de la planète</h6>
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Taille:</strong> ${params.planet_size}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Gravité:</strong> ${params.planet_gravity}G</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Température:</strong> ${params.planet_temperature}°C</p>
                    </div>
                </div>
                
                <div class="mt-2">
                    <p><strong>Habitabilité:</strong> ${params.habitability}%</p>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: ${params.habitability}%"></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <h6>Ressources estimées</h6>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Minéraux:</strong> ${params.resources.minerals}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Cristaux:</strong> ${params.resources.crystals}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Gaz:</strong> ${params.resources.gas}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Radioactifs:</strong> ${params.resources.radioactives}</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <p><strong>Population initiale:</strong> ${params.initial_population} colons</p>
                <p><strong>Temps d'établissement:</strong> ${params.establishment_time} tour(s)</p>
            </div>
        </div>
    `;
}

/**
 * Construit les détails d'un ordre de recherche
 */
function buildResearchOrderDetails(data) {
    const params = data.parameters;
    
    return `
        <div class="order-details">
            <h6 class="order-details-title">Détails de la recherche</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Technologie:</strong> ${params.technology_name}</p>
                    <p><strong>Catégorie:</strong> ${params.technology_category}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Niveau actuel:</strong> ${params.current_level}</p>
                    <p><strong>Niveau cible:</strong> ${params.target_level}</p>
                </div>
            </div>
            
            <div class="mt-3">
                <p><strong>Points de recherche requis:</strong> ${params.required_points}</p>
                <p><strong>Temps estimé:</strong> ${params.estimated_turns} tour(s)</p>
            </div>
            
            <div class="mt-3">
                <h6>Bénéfices attendus</h6>
                <p>${params.benefits}</p>
            </div>
        </div>
    `;
}

/**
 * Construit les détails d'un ordre de construction
 */
function buildBuildOrderDetails(data) {
    const params = data.parameters;
    
    let content = `
        <div class="order-details">
            <h6 class="order-details-title">Détails de la construction</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Type:</strong> ${params.build_type === 'building' ? 'Bâtiment' : 'Vaisseau'}</p>
                    <p><strong>Nom:</strong> ${params.name}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Localisation:</strong> ${params.location_name}</p>
                    <p><strong>Temps estimé:</strong> ${params.estimated_turns} tour(s)</p>
                </div>
            </div>
    `;
    
    if (params.build_type === 'building') {
        content += `
            <div class="mt-3">
                <h6>Détails du bâtiment</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Type:</strong> ${params.building_type}</p>
                        <p><strong>Niveau:</strong> ${params.level}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Taille:</strong> ${params.size} unités</p>
                        <p><strong>Énergie requise:</strong> ${params.energy_required} unités</p>
                    </div>
                </div>
                
                <div class="mt-2">
                    <h6>Effets</h6>
                    <p>${params.effects}</p>
                </div>
            </div>
        `;
    } else {
        content += `
            <div class="mt-3">
                <h6>Détails du vaisseau</h6>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Classe:</strong> ${params.ship_class}</p>
                        <p><strong>Taille:</strong> ${params.size} unités</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Équipage requis:</strong> ${params.crew_required}</p>
                        <p><strong>Maintenance:</strong> ${params.maintenance_cost} / tour</p>
                    </div>
                </div>
                
                <div class="mt-2">
                    <h6>Composants</h6>
                    <ul class="list-group">
                        ${params.components.map(component => `
                            <li class="list-group-item bg-dark text-light border-secondary">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${component.name}</strong>
                                        <div class="small text-muted">${component.type}</div>
                                    </div>
                                    <div class="text-end">
                                        <div>Niveau ${component.level}</div>
                                    </div>
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
        `;
    }
    
    content += `
            <div class="mt-3">
                <h6>Coûts</h6>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Crédits:</strong> ${params.costs.credits}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Minéraux:</strong> ${params.costs.minerals}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Cristaux:</strong> ${params.costs.crystals}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Gaz:</strong> ${params.costs.gas}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return content;
}

/**
 * Construit les détails d'un ordre diplomatique
 */
function buildDiplomaticOrderDetails(data) {
    const params = data.parameters;
    
    return `
        <div class="order-details">
            <h6 class="order-details-title">Détails de la proposition diplomatique</h6>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Type:</strong> ${getDiplomaticTypeLabel(params.diplomatic_type)}</p>
                    <p><strong>Destinataire:</strong> ${params.target_commander_name}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Durée:</strong> ${params.duration} tours</p>
                    <p><strong>Statut:</strong> ${getDiplomaticStatusLabel(params.status)}</p>
                </div>
            </div>
            
            <div class="mt-3">
                <h6>Message</h6>
                <div class="card bg-dark border-secondary">
                    <div class="card-body">
                        <p class="mb-0">${params.message}</p>
                    </div>
                </div>
            </div>
            
            ${params.diplomatic_type === 'trade_proposal' ? `
                <div class="mt-3">
                    <h6>Termes de l'échange</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Offre</h6>
                            <ul class="list-group">
                                ${params.offer.map(item => `
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        ${item.type}: ${item.amount} ${item.unit}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">Demande</h6>
                            <ul class="list-group">
                                ${params.request.map(item => `
                                    <li class="list-group-item bg-dark text-light border-secondary">
                                        ${item.type}: ${item.amount} ${item.unit}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Configure les événements des onglets
 */
function setupTabsEvents() {
    // Gestion des onglets pour conserver l'état actif
    const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
    
    tabEls.forEach(tabEl => {
        tabEl.addEventListener('shown.bs.tab', function (event) {
            // Stocker l'onglet actif dans le localStorage
            localStorage.setItem('activeTab', event.target.getAttribute('data-bs-target'));
        });
    });
    
    // Restaurer l'onglet actif au chargement de la page
    const activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
        const tab = new bootstrap.Tab(document.querySelector(`button[data-bs-target="${activeTab}"]`));
        tab.show();
    }
}

/**
 * Configure les événements des formulaires
 */
function setupFormEvents() {
    // Validation des formulaires
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Retourne le libellé d'un type d'ordre
 */
function getOrderTypeLabel(orderType) {
    switch (orderType) {
        case 'move':
            return 'Déplacement';
        case 'colonize':
            return 'Colonisation';
        case 'research':
            return 'Recherche';
        case 'build':
            return 'Construction';
        case 'diplomatic':
            return 'Diplomatie';
        default:
            return orderType.charAt(0).toUpperCase() + orderType.slice(1);
    }
}

/**
 * Retourne le libellé d'un statut d'ordre
 */
function getOrderStatusLabel(isProcessed, processingError) {
    if (processingError) {
        return '<span class="badge bg-danger">Échoué</span>';
    } else if (isProcessed) {
        return '<span class="badge bg-success">Traité</span>';
    } else {
        return '<span class="badge bg-primary">En attente</span>';
    }
}

/**
 * Retourne le libellé d'un type diplomatique
 */
function getDiplomaticTypeLabel(diplomaticType) {
    switch (diplomaticType) {
        case 'alliance_proposal':
            return 'Proposition d\'alliance';
        case 'peace_proposal':
            return 'Proposition de paix';
        case 'trade_proposal':
            return 'Proposition commerciale';
        case 'war_declaration':
            return 'Déclaration de guerre';
        default:
            return diplomaticType.charAt(0).toUpperCase() + diplomaticType.slice(1);
    }
}

/**
 * Retourne le libellé d'un statut diplomatique
 */
function getDiplomaticStatusLabel(status) {
    switch (status) {
        case 'pending':
            return '<span class="badge bg-warning text-dark">En attente</span>';
        case 'accepted':
            return '<span class="badge bg-success">Accepté</span>';
        case 'rejected':
            return '<span class="badge bg-danger">Rejeté</span>';
        default:
            return '<span class="badge bg-secondary">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }
}
