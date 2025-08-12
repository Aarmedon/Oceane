document.addEventListener('DOMContentLoaded', function() {
    // Récupération des données depuis la vue
    const reportData = JSON.parse(document.getElementById('report-data')?.textContent || '{}');
    const combatReportData = JSON.parse(document.getElementById('combat-report-data')?.textContent || '{}');
    
    // Initialisation des tooltips Bootstrap
    initTooltips();
    
    // Configuration des événements pour les rounds de combat
    setupCombatRoundEvents();
    
    // Marquer le rapport comme lu
    markReportAsRead();
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
 * Configure les événements pour les rounds de combat
 */
function setupCombatRoundEvents() {
    // Gestion des clics sur les en-têtes de round
    const roundHeaders = document.querySelectorAll('.round-header');
    
    roundHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            const toggleIcon = this.querySelector('.round-toggle i');
            
            if (isExpanded) {
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            } else {
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            }
        });
    });
}

/**
 * Marque le rapport comme lu via une requête AJAX
 */
function markReportAsRead() {
    // Récupérer l'ID du rapport depuis les données
    const reportData = JSON.parse(document.getElementById('report-data')?.textContent || '{}');
    const combatReportData = JSON.parse(document.getElementById('combat-report-data')?.textContent || '{}');
    
    let reportId;
    let reportType;
    
    if (reportData.report && reportData.report.id) {
        reportId = reportData.report.id;
        reportType = 'turn';
    } else if (combatReportData.report && combatReportData.report.id) {
        reportId = combatReportData.report.id;
        reportType = 'combat';
    }
    
    if (reportId) {
        // Récupérer le token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        // Envoyer la requête pour marquer comme lu
        fetch(`/game/reports/${reportType}/${reportId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors du marquage du rapport comme lu');
            }
            return response.json();
        })
        .then(data => {
            console.log('Rapport marqué comme lu', data);
        })
        .catch(error => {
            console.error('Erreur:', error);
        });
    }
}

/**
 * Anime la visualisation des combats
 */
function animateCombat() {
    const combatReportData = JSON.parse(document.getElementById('combat-report-data')?.textContent || '{}');
    
    if (!combatReportData.report || !combatReportData.report.combat_rounds) {
        return;
    }
    
    const rounds = combatReportData.report.combat_rounds;
    const animationContainer = document.getElementById('combatAnimation');
    
    if (!animationContainer) {
        return;
    }
    
    // Effacer le contenu existant
    animationContainer.innerHTML = '';
    
    // Créer les éléments pour l'animation
    const allyFleet = document.createElement('div');
    allyFleet.className = 'combat-fleet ally-fleet';
    
    const enemyFleet = document.createElement('div');
    enemyFleet.className = 'combat-fleet enemy-fleet';
    
    animationContainer.appendChild(allyFleet);
    animationContainer.appendChild(enemyFleet);
    
    // Ajouter les vaisseaux
    const allyShips = combatReportData.report.participants.allies.flatMap(ally => 
        ally.fleets.flatMap(fleet => fleet.ships)
    );
    
    const enemyShips = combatReportData.report.participants.enemies.flatMap(enemy => 
        enemy.fleets.flatMap(fleet => fleet.ships)
    );
    
    allyShips.forEach((ship, index) => {
        const shipElement = document.createElement('div');
        shipElement.className = 'combat-ship ally-ship';
        shipElement.id = `ally-ship-${index}`;
        shipElement.innerHTML = `
            <div class="ship-icon"><i class="fas fa-space-shuttle"></i></div>
            <div class="ship-hull-bar"></div>
            <div class="ship-shield-bar"></div>
        `;
        allyFleet.appendChild(shipElement);
    });
    
    enemyShips.forEach((ship, index) => {
        const shipElement = document.createElement('div');
        shipElement.className = 'combat-ship enemy-ship';
        shipElement.id = `enemy-ship-${index}`;
        shipElement.innerHTML = `
            <div class="ship-icon"><i class="fas fa-space-shuttle fa-flip-horizontal"></i></div>
            <div class="ship-hull-bar"></div>
            <div class="ship-shield-bar"></div>
        `;
        enemyFleet.appendChild(shipElement);
    });
    
    // Ajouter les contrôles d'animation
    const controls = document.createElement('div');
    controls.className = 'combat-animation-controls';
    controls.innerHTML = `
        <button id="playPauseBtn" class="btn btn-primary btn-sm"><i class="fas fa-play"></i> Lancer</button>
        <button id="nextRoundBtn" class="btn btn-secondary btn-sm"><i class="fas fa-step-forward"></i> Tour suivant</button>
        <button id="resetBtn" class="btn btn-danger btn-sm"><i class="fas fa-redo"></i> Réinitialiser</button>
    `;
    animationContainer.appendChild(controls);
    
    // Configurer les événements des boutons
    let isPlaying = false;
    let currentRound = 0;
    let currentAction = 0;
    let animationInterval;
    
    document.getElementById('playPauseBtn').addEventListener('click', function() {
        if (isPlaying) {
            // Pause
            clearInterval(animationInterval);
            this.innerHTML = '<i class="fas fa-play"></i> Lancer';
        } else {
            // Play
            animationInterval = setInterval(playNextAction, 1000);
            this.innerHTML = '<i class="fas fa-pause"></i> Pause';
        }
        isPlaying = !isPlaying;
    });
    
    document.getElementById('nextRoundBtn').addEventListener('click', function() {
        // Passer au round suivant
        if (currentRound < rounds.length - 1) {
            currentRound++;
            currentAction = 0;
            updateCombatLog();
        }
    });
    
    document.getElementById('resetBtn').addEventListener('click', function() {
        // Réinitialiser l'animation
        clearInterval(animationInterval);
        isPlaying = false;
        currentRound = 0;
        currentAction = 0;
        document.getElementById('playPauseBtn').innerHTML = '<i class="fas fa-play"></i> Lancer';
        resetShipStates();
        updateCombatLog();
    });
    
    // Fonction pour jouer l'action suivante
    function playNextAction() {
        if (currentRound >= rounds.length) {
            clearInterval(animationInterval);
            isPlaying = false;
            document.getElementById('playPauseBtn').innerHTML = '<i class="fas fa-play"></i> Lancer';
            return;
        }
        
        const round = rounds[currentRound];
        
        if (currentAction >= round.actions.length) {
            // Passer au round suivant
            currentRound++;
            currentAction = 0;
            
            if (currentRound >= rounds.length) {
                clearInterval(animationInterval);
                isPlaying = false;
                document.getElementById('playPauseBtn').innerHTML = '<i class="fas fa-play"></i> Lancer';
                return;
            }
        }
        
        // Jouer l'action actuelle
        const action = round.actions[currentAction];
        animateAction(action);
        
        // Mettre à jour le journal de combat
        updateCombatLog();
        
        // Passer à l'action suivante
        currentAction++;
    }
    
    // Fonction pour animer une action
    function animateAction(action) {
        // Trouver les indices des vaisseaux concernés
        const shipIndex = findShipIndex(action.ship_name, action.side === 'ally' ? allyShips : enemyShips);
        const targetIndex = action.target_name ? findShipIndex(action.target_name, action.side === 'ally' ? enemyShips : allyShips) : -1;
        
        // Animer en fonction du type d'action
        switch (action.type) {
            case 'attack':
                animateAttack(action.side, shipIndex, targetIndex, action.hit, action.critical);
                break;
            case 'shield_regen':
                animateShieldRegen(action.side, shipIndex);
                break;
            case 'repair':
                animateRepair(action.side, shipIndex);
                break;
            case 'destroyed':
                animateDestruction(action.side, shipIndex);
                break;
        }
    }
    
    // Fonction pour trouver l'indice d'un vaisseau par son nom
    function findShipIndex(shipName, ships) {
        return ships.findIndex(ship => ship.name === shipName);
    }
    
    // Fonction pour animer une attaque
    function animateAttack(side, shipIndex, targetIndex, hit, critical) {
        const shipElement = document.getElementById(`${side}-ship-${shipIndex}`);
        const targetElement = document.getElementById(`${side === 'ally' ? 'enemy' : 'ally'}-ship-${targetIndex}`);
        
        if (!shipElement || !targetElement) return;
        
        // Animer le vaisseau attaquant
        shipElement.classList.add('attacking');
        setTimeout(() => shipElement.classList.remove('attacking'), 500);
        
        // Créer un projectile
        const projectile = document.createElement('div');
        projectile.className = `combat-projectile ${side}-projectile ${critical ? 'critical' : ''}`;
        animationContainer.appendChild(projectile);
        
        // Calculer la trajectoire
        const shipRect = shipElement.getBoundingClientRect();
        const targetRect = targetElement.getBoundingClientRect();
        const containerRect = animationContainer.getBoundingClientRect();
        
        const startX = (shipRect.left + shipRect.right) / 2 - containerRect.left;
        const startY = (shipRect.top + shipRect.bottom) / 2 - containerRect.top;
        const endX = (targetRect.left + targetRect.right) / 2 - containerRect.left;
        const endY = (targetRect.top + targetRect.bottom) / 2 - containerRect.top;
        
        // Positionner et animer le projectile
        projectile.style.left = `${startX}px`;
        projectile.style.top = `${startY}px`;
        
        setTimeout(() => {
            projectile.style.left = `${endX}px`;
            projectile.style.top = `${endY}px`;
        }, 10);
        
        // Gérer l'impact
        setTimeout(() => {
            animationContainer.removeChild(projectile);
            
            if (hit) {
                targetElement.classList.add('hit');
                setTimeout(() => targetElement.classList.remove('hit'), 300);
            } else {
                const miss = document.createElement('div');
                miss.className = 'combat-miss';
                miss.textContent = 'MISS';
                miss.style.left = `${endX}px`;
                miss.style.top = `${endY}px`;
                animationContainer.appendChild(miss);
                
                setTimeout(() => animationContainer.removeChild(miss), 500);
            }
        }, 500);
    }
    
    // Fonction pour animer la régénération de bouclier
    function animateShieldRegen(side, shipIndex) {
        const shipElement = document.getElementById(`${side}-ship-${shipIndex}`);
        if (!shipElement) return;
        
        const shieldEffect = document.createElement('div');
        shieldEffect.className = 'shield-regen-effect';
        shipElement.appendChild(shieldEffect);
        
        setTimeout(() => shipElement.removeChild(shieldEffect), 1000);
    }
    
    // Fonction pour animer une réparation
    function animateRepair(side, shipIndex) {
        const shipElement = document.getElementById(`${side}-ship-${shipIndex}`);
        if (!shipElement) return;
        
        const repairEffect = document.createElement('div');
        repairEffect.className = 'repair-effect';
        repairEffect.innerHTML = '<i class="fas fa-wrench"></i>';
        shipElement.appendChild(repairEffect);
        
        setTimeout(() => shipElement.removeChild(repairEffect), 1000);
    }
    
    // Fonction pour animer une destruction
    function animateDestruction(side, shipIndex) {
        const shipElement = document.getElementById(`${side}-ship-${shipIndex}`);
        if (!shipElement) return;
        
        shipElement.classList.add('destroyed');
        
        const explosionEffect = document.createElement('div');
        explosionEffect.className = 'explosion-effect';
        shipElement.appendChild(explosionEffect);
        
        setTimeout(() => {
            if (shipElement.contains(explosionEffect)) {
                shipElement.removeChild(explosionEffect);
            }
        }, 1000);
    }
    
    // Fonction pour réinitialiser l'état des vaisseaux
    function resetShipStates() {
        document.querySelectorAll('.combat-ship').forEach(ship => {
            ship.classList.remove('destroyed', 'hit', 'attacking');
            
            // Supprimer les effets
            const effects = ship.querySelectorAll('.shield-regen-effect, .repair-effect, .explosion-effect');
            effects.forEach(effect => ship.removeChild(effect));
        });
    }
    
    // Fonction pour mettre à jour le journal de combat
    function updateCombatLog() {
        const combatLog = document.getElementById('combatLog');
        if (!combatLog) return;
        
        combatLog.innerHTML = '';
        
        if (currentRound < rounds.length) {
            const round = rounds[currentRound];
            
            const roundTitle = document.createElement('h6');
            roundTitle.textContent = `Round ${currentRound + 1}`;
            combatLog.appendChild(roundTitle);
            
            const actionsList = document.createElement('ul');
            actionsList.className = 'list-unstyled';
            
            for (let i = 0; i < Math.min(currentAction, round.actions.length); i++) {
                const action = round.actions[i];
                const actionItem = document.createElement('li');
                actionItem.className = `combat-log-item ${action.side}-log`;
                
                let actionText = '';
                switch (action.type) {
                    case 'attack':
                        actionText = `${action.ship_name} attaque ${action.target_name} et `;
                        if (action.hit) {
                            actionText += `inflige ${action.damage} points de dégâts`;
                            if (action.critical) actionText += ' (Critique!)';
                        } else {
                            actionText += 'rate sa cible';
                        }
                        break;
                    case 'shield_regen':
                        actionText = `${action.ship_name} régénère ${action.amount} points de bouclier`;
                        break;
                    case 'repair':
                        actionText = `${action.ship_name} répare ${action.amount} points de coque`;
                        break;
                    case 'destroyed':
                        actionText = `${action.ship_name} est détruit!`;
                        break;
                    default:
                        actionText = action.description || 'Action inconnue';
                }
                
                actionItem.textContent = actionText;
                actionsList.appendChild(actionItem);
            }
            
            combatLog.appendChild(actionsList);
        }
    }
}

/**
 * Génère un graphique des ressources
 */
function generateResourcesChart() {
    const reportData = JSON.parse(document.getElementById('report-data')?.textContent || '{}');
    
    if (!reportData.report || !reportData.report.financial_report) {
        return;
    }
    
    const ctx = document.getElementById('resourcesChart');
    if (!ctx) return;
    
    const income = reportData.report.financial_report.income;
    const expenses = reportData.report.financial_report.expenses;
    
    // Convertir les données pour le graphique
    const incomeData = Object.entries(income).map(([key, value]) => ({
        category: key,
        value: value
    }));
    
    const expensesData = Object.entries(expenses).map(([key, value]) => ({
        category: key,
        value: value
    }));
    
    // Créer le graphique
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [...new Set([...incomeData.map(d => d.category), ...expensesData.map(d => d.category)])],
            datasets: [
                {
                    label: 'Revenus',
                    data: incomeData.map(d => d.value),
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Dépenses',
                    data: expensesData.map(d => d.value),
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Revenus et Dépenses'
                }
            }
        }
    });
}

/**
 * Télécharge le rapport au format PDF
 */
function downloadReportAsPdf() {
    const reportData = JSON.parse(document.getElementById('report-data')?.textContent || '{}');
    const combatReportData = JSON.parse(document.getElementById('combat-report-data')?.textContent || '{}');
    
    let reportId;
    let reportType;
    
    if (reportData.report && reportData.report.id) {
        reportId = reportData.report.id;
        reportType = 'turn';
    } else if (combatReportData.report && combatReportData.report.id) {
        reportId = combatReportData.report.id;
        reportType = 'combat';
    }
    
    if (reportId) {
        window.location.href = `/game/reports/${reportType}/${reportId}/download`;
    }
}
