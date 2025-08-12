document.addEventListener('DOMContentLoaded', function() {
    // Récupération des données depuis la vue
    const techData = JSON.parse(document.getElementById('technologies-data').textContent);
    const technologies = techData.technologies;
    const currentResearch = techData.current_research;
    
    // Initialisation de l'arbre technologique
    initTechTree(technologies, currentResearch);
    
    // Configuration des filtres de catégories
    setupCategoryFilters();
    
    // Configuration des événements pour les cartes de technologies
    setupTechCardEvents();
});

/**
 * Initialise l'arbre technologique visuel
 */
function initTechTree(technologies, currentResearch) {
    const techTree = document.getElementById('techTree');
    if (!techTree) return;
    
    // Effacer le contenu existant
    techTree.innerHTML = '';
    
    // Créer une structure hiérarchique des technologies
    const techHierarchy = buildTechHierarchy(technologies);
    
    // Calculer les positions des nœuds dans l'arbre
    const treeLayout = calculateTreeLayout(techHierarchy);
    
    // Dessiner les connexions entre les technologies
    drawTechConnections(treeLayout, technologies);
    
    // Dessiner les nœuds de technologies
    drawTechNodes(treeLayout, technologies, currentResearch);
}

/**
 * Construit une hiérarchie des technologies basée sur les prérequis
 */
function buildTechHierarchy(technologies) {
    // Créer un mapping des technologies par ID
    const techMap = {};
    technologies.forEach(tech => {
        techMap[tech.id] = {
            ...tech,
            children: []
        };
    });
    
    // Construire l'arbre
    const rootTechs = [];
    
    technologies.forEach(tech => {
        if (tech.prerequisite_id) {
            // Cette technologie a un prérequis, l'ajouter comme enfant
            if (techMap[tech.prerequisite_id]) {
                techMap[tech.prerequisite_id].children.push(techMap[tech.id]);
            }
        } else {
            // Pas de prérequis, c'est une technologie racine
            rootTechs.push(techMap[tech.id]);
        }
    });
    
    return rootTechs;
}

/**
 * Calcule la disposition de l'arbre technologique
 */
function calculateTreeLayout(techHierarchy) {
    const treeLayout = [];
    const levelWidth = 180;  // Espacement horizontal entre les niveaux
    const nodeSpacing = 150; // Espacement vertical entre les nœuds
    
    // Fonction récursive pour calculer les positions
    function positionNode(node, level, order, totalInLevel) {
        const x = level * levelWidth + 100;
        
        // Calculer la position y en fonction du nombre total de nœuds à ce niveau
        const levelHeight = totalInLevel * nodeSpacing;
        const startY = (totalInLevel - 1) * nodeSpacing / 2;
        const y = startY - (order * nodeSpacing) + 200;
        
        treeLayout.push({
            id: node.id,
            x: x,
            y: y,
            level: level,
            order: order,
            prerequisite_id: node.prerequisite_id
        });
        
        // Positionner les enfants
        if (node.children && node.children.length > 0) {
            const childrenCount = node.children.length;
            node.children.forEach((child, index) => {
                positionNode(child, level + 1, index, childrenCount);
            });
        }
    }
    
    // Positionner les technologies racines
    const rootCount = techHierarchy.length;
    techHierarchy.forEach((rootTech, index) => {
        positionNode(rootTech, 0, index, rootCount);
    });
    
    return treeLayout;
}

/**
 * Dessine les connexions entre les technologies dans l'arbre
 */
function drawTechConnections(treeLayout, technologies) {
    const techTree = document.getElementById('techTree');
    
    // Créer un mapping des positions par ID
    const positionMap = {};
    treeLayout.forEach(node => {
        positionMap[node.id] = { x: node.x, y: node.y };
    });
    
    // Dessiner les connexions
    treeLayout.forEach(node => {
        if (node.prerequisite_id && positionMap[node.prerequisite_id]) {
            const start = positionMap[node.prerequisite_id];
            const end = { x: node.x, y: node.y };
            
            // Trouver les technologies correspondantes
            const tech = technologies.find(t => t.id === node.id);
            const prereqTech = technologies.find(t => t.id === node.prerequisite_id);
            
            // Déterminer si la connexion est "recherchée"
            const isResearched = tech && prereqTech && 
                                tech.level > 0 && 
                                prereqTech.level >= tech.prerequisite_level;
            
            // Calculer la longueur et l'angle de la connexion
            const dx = end.x - start.x;
            const dy = end.y - start.y;
            const length = Math.sqrt(dx * dx + dy * dy);
            const angle = Math.atan2(dy, dx) * 180 / Math.PI;
            
            // Créer l'élément de connexion
            const connection = document.createElement('div');
            connection.className = `tech-connection ${isResearched ? 'researched' : ''}`;
            connection.style.width = `${length}px`;
            connection.style.left = `${start.x}px`;
            connection.style.top = `${start.y}px`;
            connection.style.transform = `rotate(${angle}deg)`;
            
            techTree.appendChild(connection);
        }
    });
}

/**
 * Dessine les nœuds des technologies dans l'arbre
 */
function drawTechNodes(treeLayout, technologies, currentResearch) {
    const techTree = document.getElementById('techTree');
    
    treeLayout.forEach(layout => {
        // Trouver la technologie correspondante
        const tech = technologies.find(t => t.id === layout.id);
        if (!tech) return;
        
        // Créer le nœud
        const node = document.createElement('div');
        
        // Déterminer l'état du nœud
        let nodeClass = 'tech-node';
        if (tech.level > 0) {
            nodeClass += ' researched';
        } else if (tech.is_researchable) {
            nodeClass += ' researchable';
        } else {
            nodeClass += ' locked';
        }
        
        // Si c'est la recherche en cours
        if (currentResearch && currentResearch.id === tech.id) {
            nodeClass += ' current-research';
        }
        
        node.className = nodeClass;
        node.setAttribute('data-tech-id', tech.id);
        node.setAttribute('data-category', tech.category);
        node.style.left = `${layout.x}px`;
        node.style.top = `${layout.y}px`;
        
        // Contenu du nœud
        node.innerHTML = `
            <div class="tech-node-icon" style="background-image: url('/images/technologies/${tech.image_path}')"></div>
            <div class="tech-node-name">${tech.name}</div>
            <div class="tech-node-level">Niveau ${tech.level}/${tech.max_level}</div>
        `;
        
        // Événement au clic
        node.addEventListener('click', function() {
            showTechDetails(tech.id);
        });
        
        techTree.appendChild(node);
    });
}

/**
 * Configure les filtres de catégories
 */
function setupCategoryFilters() {
    const filterButtons = document.querySelectorAll('.tech-filter-group button');
    const techCategories = document.querySelectorAll('.tech-category');
    const techNodes = document.querySelectorAll('.tech-node');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Mise à jour des boutons actifs
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            // Filtrer les catégories de technologies
            techCategories.forEach(category => {
                if (filter === 'all' || category.getAttribute('data-category') === filter) {
                    category.style.display = '';
                } else {
                    category.style.display = 'none';
                }
            });
            
            // Filtrer les nœuds dans l'arbre
            techNodes.forEach(node => {
                if (filter === 'all' || node.getAttribute('data-category') === filter) {
                    node.style.opacity = '1';
                } else {
                    node.style.opacity = '0.3';
                }
            });
        });
    });
}

/**
 * Configure les événements pour les cartes de technologies
 */
function setupTechCardEvents() {
    const techCards = document.querySelectorAll('.tech-card');
    
    techCards.forEach(card => {
        card.addEventListener('click', function() {
            const techId = this.getAttribute('data-tech-id');
            showTechDetails(techId);
        });
    });
}

/**
 * Affiche les détails d'une technologie
 */
function showTechDetails(techId) {
    const modal = document.getElementById('techDetailsModal');
    const modalTitle = document.getElementById('techDetailsTitle');
    const modalContent = document.getElementById('techDetailsContent');
    const researchButton = document.getElementById('researchTechButton');
    
    // Afficher le modal
    const techModal = new bootstrap.Modal(modal);
    techModal.show();
    
    // Charger les détails de la technologie
    fetch(`/game/technologies/${techId}/details`)
        .then(response => response.json())
        .then(data => {
            modalTitle.textContent = data.name;
            
            // Construire le contenu du modal
            let content = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="tech-image mb-3" style="height: 150px; background-image: url('/images/technologies/${data.image_path || 'default.jpg'}'); background-size: contain; background-position: center; background-repeat: no-repeat;"></div>
                        
                        <h6>Informations</h6>
                        <ul class="list-group list-group-flush bg-dark mb-3">
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Catégorie:</span>
                                <strong>${data.category}</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Niveau actuel:</span>
                                <strong>${data.current_level}/${data.max_level}</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Coût de base:</span>
                                <strong>${data.base_research_cost} points</strong>
                            </li>
                            <li class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between">
                                <span>Multiplicateur:</span>
                                <strong>×${data.level_multiplier}</strong>
                            </li>
                        </ul>
                        
                        ${data.prerequisite ? `
                        <h6>Prérequis</h6>
                        <div class="card bg-dark border-secondary mb-3">
                            <div class="card-body p-2">
                                <div class="d-flex align-items-center">
                                    <div class="tech-mini-icon me-2" style="width: 30px; height: 30px; background-image: url('/images/technologies/${data.prerequisite.image_path || 'default.jpg'}'); background-size: contain; background-position: center; background-repeat: no-repeat;"></div>
                                    <div>
                                        <div>${data.prerequisite.name}</div>
                                        <div class="small text-muted">Niveau ${data.prerequisite_level} requis</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="col-md-8">
                        <h6>Description</h6>
                        <p>${data.description}</p>
                        
                        ${data.current_level > 0 ? `
                        <div class="alert alert-info">
                            <h6>Bonus actuels (Niveau ${data.current_level})</h6>
                            <p>${data.current_bonus || 'Aucun bonus spécifique'}</p>
                        </div>
                        ` : ''}
                        
                        ${data.next_level_bonus ? `
                        <div class="alert alert-secondary">
                            <h6>Prochain niveau (${data.current_level + 1})</h6>
                            <p>${data.next_level_bonus}</p>
                        </div>
                        ` : ''}
                        
                        ${data.components && data.components.length > 0 ? `
                        <h6>Composants débloqués</h6>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Niveau requis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.components.map(component => `
                                    <tr>
                                        <td>${component.name}</td>
                                        <td>${component.type}</td>
                                        <td>${component.required_level}</td>
                                    </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : ''}
                        
                        ${data.buildings && data.buildings.length > 0 ? `
                        <h6>Bâtiments débloqués</h6>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Niveau requis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.buildings.map(building => `
                                    <tr>
                                        <td>${building.name}</td>
                                        <td>${building.type}</td>
                                        <td>${building.required_level}</td>
                                    </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            modalContent.innerHTML = content;
            
            // Configurer le bouton de recherche
            if (data.is_researchable && !data.current_research) {
                researchButton.style.display = '';
                researchButton.onclick = function() {
                    window.location.href = `/game/technologies/${data.id}/research`;
                };
            } else {
                researchButton.style.display = 'none';
            }
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement des détails de la technologie.
                </div>
            `;
            console.error('Erreur lors du chargement des détails:', error);
        });
}
