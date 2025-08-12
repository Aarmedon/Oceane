document.addEventListener('DOMContentLoaded', function() {
    // Récupération des données depuis la vue
    const fleetData = JSON.parse(document.getElementById('fleet-data').textContent);
    
    // Initialisation de l'affichage de la flotte
    initFleetDisplay();
    
    // Gestion des événements pour les vaisseaux et les actions de la flotte
    setupShipEvents();
    setupModalEvents();
    
    // Si le modal de mouvement est ouvert, initialiser la carte
    const moveFleetModal = document.getElementById('moveFleetModal');
    if (moveFleetModal) {
        moveFleetModal.addEventListener('shown.bs.modal', function() {
            initMovementMap();
        });
    }
    
    // Action de dissolution de flotte
    const dissolveAction = document.getElementById('dissolveAction');
    const dissolveActionValue = document.getElementById('dissolveActionValue');
    if (dissolveAction && dissolveActionValue) {
        dissolveAction.addEventListener('change', function() {
            dissolveActionValue.value = this.value;
        });
    }
});

/**
 * Initialise l'affichage visuel de la flotte
 */
function initFleetDisplay() {
    const fleetDisplay = document.getElementById('fleetDisplay');
    if (!fleetDisplay) return;
    
    const fleetData = JSON.parse(document.getElementById('fleet-data').textContent);
    const fleet = fleetData.fleet;
    
    // Effacer le contenu existant
    fleetDisplay.innerHTML = '';
    
    // Si la flotte n'a pas de vaisseaux, afficher un message
    if (fleet.ships_count === 0) {
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'd-flex justify-content-center align-items-center h-100';
        emptyMessage.innerHTML = '<p class="text-muted">Cette flotte ne contient aucun vaisseau.</p>';
        fleetDisplay.appendChild(emptyMessage);
        return;
    }
    
    // Créer une formation de base pour les vaisseaux
    // Les vaisseaux seront disposés en cercle ou en formation tactique selon le statut
    const ships = document.querySelectorAll('.ship-table-row');
    const centerX = fleetDisplay.offsetWidth / 2;
    const centerY = fleetDisplay.offsetHeight / 2;
    
    // Créer un élément pour représenter le point central de la formation
    const fleetCenter = document.createElement('div');
    fleetCenter.className = 'fleet-center';
    fleetCenter.style.position = 'absolute';
    fleetCenter.style.width = '20px';
    fleetCenter.style.height = '20px';
    fleetCenter.style.borderRadius = '50%';
    fleetCenter.style.border = '2px solid rgba(255, 255, 255, 0.5)';
    fleetCenter.style.left = `${centerX}px`;
    fleetCenter.style.top = `${centerY}px`;
    fleetCenter.style.transform = 'translate(-50%, -50%)';
    fleetDisplay.appendChild(fleetCenter);
    
    // Formation différente selon le statut de la flotte
    const isMoving = fleet.status === 1; // 1 = en mouvement
    const radius = Math.min(centerX, centerY) * 0.6;
    
    ships.forEach((shipRow, index) => {
        const shipId = shipRow.id.replace('ship-row-', '');
        const shipName = shipRow.querySelector('td:first-child').textContent;
        const shipType = shipRow.querySelector('td:nth-child(2)').textContent;
        
        // Position en fonction du statut de la flotte
        let posX, posY;
        if (isMoving) {
            // Formation en V pour les flottes en mouvement
            const angle = (index / ships.length) * 120 - 60; // -60 à 60 degrés
            const distance = radius * (0.5 + index / (ships.length * 2));
            posX = centerX + distance * Math.cos(angle * Math.PI / 180);
            posY = centerY + distance * Math.sin(angle * Math.PI / 180);
        } else {
            // Formation en cercle pour les flottes à quai
            const angle = (index / ships.length) * 360;
            posX = centerX + radius * Math.cos(angle * Math.PI / 180);
            posY = centerY + radius * Math.sin(angle * Math.PI / 180);
        }
        
        // Ligne de formation
        const line = document.createElement('div');
        line.className = 'fleet-formation-line';
        line.style.width = `${Math.sqrt(Math.pow(posX - centerX, 2) + Math.pow(posY - centerY, 2))}px`;
        line.style.left = `${centerX}px`;
        line.style.top = `${centerY}px`;
        
        // Calculer l'angle de la ligne
        const angle = Math.atan2(posY - centerY, posX - centerX) * 180 / Math.PI;
        line.style.transform = `rotate(${angle}deg)`;
        fleetDisplay.appendChild(line);
        
        // Créer l'élément du vaisseau
        const ship = document.createElement('div');
        ship.className = 'fleet-ship';
        ship.setAttribute('data-ship-id', shipId);
        ship.style.left = `${posX}px`;
        ship.style.top = `${posY}px`;
        
        // Vaisseau représenté par un triangle
        const shipSize = 15;
        ship.innerHTML = `
            <svg width="${shipSize * 2}" height="${shipSize}" viewBox="0 0 ${shipSize * 2} ${shipSize}" xmlns="http://www.w3.org/2000/svg">
                <polygon points="${shipSize},0 ${shipSize * 2},${shipSize} 0,${shipSize}" fill="#3182ce" />
            </svg>
            <div class="ship-name" style="position: absolute; top: ${shipSize + 5}px; left: 50%; transform: translateX(-50%); font-size: 10px; white-space: nowrap;">${shipName}</div>
        `;
        
        // Rotation selon la direction
        if (isMoving) {
            ship.style.transform = 'translate(-50%, -50%) rotate(0deg)';
        } else {
            const rotationAngle = (index / ships.length) * 360;
            ship.style.transform = `translate(-50%, -50%) rotate(${rotationAngle + 90}deg)`;
        }
        
        // Lier l'événement click à l'affichage des détails
        ship.addEventListener('click', function() {
            showShipDetails(shipId);
        });
        
        // Surligner la ligne correspondante dans le tableau au survol
        ship.addEventListener('mouseenter', function() {
            shipRow.classList.add('highlighted');
        });
        
        ship.addEventListener('mouseleave', function() {
            shipRow.classList.remove('highlighted');
        });
        
        fleetDisplay.appendChild(ship);
    });
    
    // Si la flotte est en mouvement, ajouter une animation
    if (isMoving) {
        // Ajouter un effet d'animation pour les vaisseaux en mouvement
        const ships = document.querySelectorAll('.fleet-ship');
        ships.forEach(ship => {
            ship.style.animation = 'pulse 2s infinite';
        });
        
        // Ajouter une animation CSS
        const style = document.createElement('style');
        style.innerHTML = `
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.7; }
                100% { opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
}

/**
 * Configurer les événements pour les vaisseaux
 */
function setupShipEvents() {
    // Boutons de détails des vaisseaux
    const shipDetailButtons = document.querySelectorAll('.ship-details');
    shipDetailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const shipId = this.getAttribute('data-ship-id');
            showShipDetails(shipId);
        });
    });
    
    // Surligner l'élément vaisseau correspondant au survol d'une ligne du tableau
    const shipRows = document.querySelectorAll('.ship-table-row');
    shipRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            const shipId = this.id.replace('ship-row-', '');
            const shipElement = document.querySelector(`.fleet-ship[data-ship-id="${shipId}"]`);
            if (shipElement) {
                shipElement.style.zIndex = '10';
                shipElement.style.transform = 'translate(-50%, -50%) scale(1.2)';
            }
        });
        
        row.addEventListener('mouseleave', function() {
            const shipId = this.id.replace('ship-row-', '');
            const shipElement = document.querySelector(`.fleet-ship[data-ship-id="${shipId}"]`);
            if (shipElement) {
                shipElement.style.zIndex = '1';
                
                // Restaurer la transformation originale selon le statut de la flotte
                const fleetData = JSON.parse(document.getElementById('fleet-data').textContent);
                const fleet = fleetData.fleet;
                const isMoving = fleet.status === 1;
                const index = Array.from(shipRows).indexOf(this);
                
                if (isMoving) {
                    shipElement.style.transform = 'translate(-50%, -50%) rotate(0deg)';
                } else {
                    const rotationAngle = (index / shipRows.length) * 360;
                    shipElement.style.transform = `translate(-50%, -50%) rotate(${rotationAngle + 90}deg)`;
                }
            }
        });
    });
}

/**
 * Affiche les détails d'un vaisseau
 */
function showShipDetails(shipId) {
    const modal = document.getElementById('shipDetailsModal');
    const modalTitle = document.getElementById('shipDetailsTitle');
    const modalContent = document.getElementById('shipDetailsContent');
    
    // Afficher le modal
    const shipModal = new bootstrap.Modal(modal);
    shipModal.show();
    
    // Charger les détails du vaisseau
    fetch(`/game/ships/${shipId}/details`)
        .then(response => response.json())
        .then(data => {
            modalTitle.textContent = data.name + ' (' + data.design.name + ')';
            
            // Construire le contenu du modal
            let content = `
                <div class="row">
                    <div class="col-md-5">
                        <div class="ship-image mb-3" style="height: 150px; background-image: url('/images/ships/${data.design.image || 'default.jpg'}'); background-size: contain; background-position: center; background-repeat: no-repeat;"></div>
                        
                        <h6>Caractéristiques</h6>
                        <ul class="list-group list-group-flush bg-dark mb-3">
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Type:</span>
                                <strong>${data.design.name}</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Coque:</span>
                                <strong>${data.hull_points}/${data.max_hull_points}</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Bouclier:</span>
                                <strong>${data.shield_points}/${data.max_shield_points}</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Vitesse:</span>
                                <strong>${data.design.speed} UA/tour</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Expérience:</span>
                                <strong>${data.experience}</strong>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="col-md-7">
                        <h6>Composants</h6>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Nom</th>
                                        <th>Qté</th>
                                        <th>État</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                                
            if (data.components && data.components.length > 0) {
                data.components.forEach(component => {
                    content += `
                        <tr>
                            <td>${component.type}</td>
                            <td>${component.name}</td>
                            <td>${component.pivot.quantity}</td>
                            <td>
                                ${component.pivot.status === 'operational' ? 
                                    '<span class="badge bg-success">Opérationnel</span>' : 
                                    '<span class="badge bg-danger">Endommagé</span>'}
                            </td>
                        </tr>`;
                });
            } else {
                content += `<tr><td colspan="4" class="text-center">Aucun composant installé</td></tr>`;
            }
                                
            content += `
                                </tbody>
                            </table>
                        </div>
                        
                        <h6 class="mt-3">Capacités</h6>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <tbody>
                                    <tr>
                                        <td>Puissance de feu</td>
                                        <td>${data.firepower || 0}</td>
                                    </tr>
                                    <tr>
                                        <td>Défense</td>
                                        <td>${data.defense || 0}</td>
                                    </tr>
                                    <tr>
                                        <td>Portée de scan</td>
                                        <td>${data.scan_range || 0} UA</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            modalContent.innerHTML = content;
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement des détails du vaisseau.
                </div>
            `;
            console.error('Erreur lors du chargement des détails:', error);
        });
}

/**
 * Configuration des événements pour les modals
 */
function setupModalEvents() {
    // Formulaires dans les modals
    const modalForms = document.querySelectorAll('.modal form');
    modalForms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
            }
        });
    });
}

/**
 * Initialise la carte de déplacement pour la flotte
 */
function initMovementMap() {
    const movementMap = document.getElementById('movementMap');
    if (!movementMap) return;
    
    // Récupérer les données
    const fleetData = JSON.parse(document.getElementById('fleet-data').textContent);
    const fleet = fleetData.fleet;
    const currentSystem = fleetData.current_system;
    const nearbySystems = fleetData.nearby_systems;
    
    // Effacer la carte existante
    movementMap.innerHTML = '';
    
    // Déterminer la taille de la carte et calculer les échelles
    const width = movementMap.offsetWidth;
    const height = movementMap.offsetHeight;
    
    // Calculer les limites de la carte
    let minX = currentSystem.position_x;
    let maxX = currentSystem.position_x;
    let minY = currentSystem.position_y;
    let maxY = currentSystem.position_y;
    
    nearbySystems.forEach(system => {
        minX = Math.min(minX, system.position_x);
        maxX = Math.max(maxX, system.position_x);
        minY = Math.min(minY, system.position_y);
        maxY = Math.max(maxY, system.position_y);
    });
    
    // Ajouter une marge
    const padding = Math.max(maxX - minX, maxY - minY) * 0.2;
    minX -= padding;
    maxX += padding;
    minY -= padding;
    maxY += padding;
    
    // Calculer les échelles
    const scaleX = width / (maxX - minX);
    const scaleY = height / (maxY - minY);
    
    // Fonction pour convertir les coordonnées en position sur la carte
    function mapPosition(x, y) {
        return {
            x: (x - minX) * scaleX,
            y: (y - minY) * scaleY
        };
    }
    
    // Ajouter le système actuel
    const currentPos = mapPosition(currentSystem.position_x, currentSystem.position_y);
    const currentMarker = document.createElement('div');
    currentMarker.className = 'system-marker current';
    currentMarker.style.left = currentPos.x + 'px';
    currentMarker.style.top = currentPos.y + 'px';
    
    const currentName = document.createElement('div');
    currentName.className = 'system-name';
    currentName.textContent = currentSystem.name;
    currentMarker.appendChild(currentName);
    
    movementMap.appendChild(currentMarker);
    
    // Variable pour stocker le système sélectionné
    let selectedSystem = null;
    
    // Ajouter les systèmes proches
    nearbySystems.forEach(system => {
        const pos = mapPosition(system.position_x, system.position_y);
        
        // Calculer la distance au système actuel
        const distance = Math.sqrt(
            Math.pow(system.position_x - currentSystem.position_x, 2) + 
            Math.pow(system.position_y - currentSystem.position_y, 2)
        );
        
        const inRange = distance <= fleet.max_speed;
        
        const marker = document.createElement('div');
        marker.className = 'system-marker ' + (inRange ? 'in-range' : 'out-of-range');
        marker.setAttribute('data-system-id', system.id);
        marker.setAttribute('data-system-name', system.name);
        marker.setAttribute('data-distance', distance.toFixed(2));
        marker.style.left = pos.x + 'px';
        marker.style.top = pos.y + 'px';
        
        const name = document.createElement('div');
        name.className = 'system-name';
        name.textContent = system.name;
        marker.appendChild(name);
        
        // Événement au clic
        marker.addEventListener('click', function() {
            if (!inRange) {
                document.getElementById('movementAlert').textContent = 'Ce système est hors de portée de la flotte.';
                document.getElementById('movementAlert').classList.remove('d-none');
                return;
            }
            
            // Désélectionner le système précédent s'il existe
            if (selectedSystem) {
                selectedSystem.classList.remove('selected');
            }
            
            // Sélectionner ce système
            this.classList.add('selected');
            selectedSystem = this;
            
            // Mettre à jour le formulaire
            document.getElementById('destinationSystemId').value = this.getAttribute('data-system-id');
            document.getElementById('selectedSystemName').value = this.getAttribute('data-system-name');
            document.getElementById('movementDistance').value = this.getAttribute('data-distance') + ' UA';
            
            const travelTime = Math.ceil(distance / fleet.max_speed);
            document.getElementById('movementTime').value = travelTime + ' tour' + (travelTime > 1 ? 's' : '');
            
            // Activer le bouton
            document.getElementById('moveFleetButton').disabled = false;
            
            // Cacher l'alerte si elle était visible
            document.getElementById('movementAlert').classList.add('d-none');
            
            // Dessiner la trajectoire
            drawPath(currentPos, pos);
        });
        
        movementMap.appendChild(marker);
    });
    
    // Fonction pour dessiner une trajectoire entre deux points
    function drawPath(start, end) {
        // Supprimer les trajectoires existantes
        const oldPaths = movementMap.querySelectorAll('.movement-path');
        oldPaths.forEach(path => path.remove());
        
        // Calculer la longueur et l'angle
        const dx = end.x - start.x;
        const dy = end.y - start.y;
        const length = Math.sqrt(dx * dx + dy * dy);
        const angle = Math.atan2(dy, dx) * 180 / Math.PI;
        
        // Créer la trajectoire
        const path = document.createElement('div');
        path.className = 'movement-path';
        path.style.left = start.x + 'px';
        path.style.top = start.y + 'px';
        path.style.width = length + 'px';
        path.style.transform = `rotate(${angle}deg)`;
        
        movementMap.appendChild(path);
    }
}
