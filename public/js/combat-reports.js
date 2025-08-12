/**
 * Gestion des rapports de combat
 * Océane 2 - Interface de rapports
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    initializeTooltips();
    
    // Gestion des rounds de combat
    setupCombatRounds();
    
    // Initialiser les graphiques si la bibliothèque Chart.js est disponible
    if (typeof Chart !== 'undefined') {
        initializeCombatCharts();
    }
    
    // Marquer le rapport comme lu
    markReportAsRead();
});

/**
 * Initialise les tooltips Bootstrap
 */
function initializeTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Configure les interactions pour les rounds de combat
 */
function setupCombatRounds() {
    // Gestion de l'expansion/réduction des rounds
    const roundHeaders = document.querySelectorAll('.round-header');
    
    roundHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const roundContent = this.nextElementSibling;
            const isCollapsed = roundContent.classList.contains('show');
            const icon = this.querySelector('.round-toggle i');
            
            if (isCollapsed) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    });
    
    // Animation pour les actions de combat
    animateCombatActions();
}

/**
 * Anime les actions de combat pour une meilleure visualisation
 */
function animateCombatActions() {
    const combatActions = document.querySelectorAll('.combat-action');
    
    // Si IntersectionObserver est disponible, utiliser pour animer au scroll
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });
        
        combatActions.forEach(action => {
            observer.observe(action);
        });
    } else {
        // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
        combatActions.forEach(action => {
            action.classList.add('animated');
        });
    }
}

/**
 * Initialise les graphiques pour visualiser les statistiques de combat
 */
function initializeCombatCharts() {
    // Récupérer les données JSON si disponibles
    const dataElement = document.getElementById('combat-report-data');
    if (!dataElement) return;
    
    try {
        const reportData = JSON.parse(dataElement.textContent);
        
        // Graphique des pertes
        createLossesChart(reportData.losses);
        
        // Graphique de la composition des forces
        createForcesCompositionChart(reportData.forces);
        
        // Graphique de l'évolution du combat
        createCombatProgressionChart(reportData.progression);
    } catch (error) {
        console.error('Erreur lors de l\'initialisation des graphiques:', error);
    }
}

/**
 * Crée un graphique montrant les pertes de chaque côté
 */
function createLossesChart(lossesData) {
    const ctx = document.getElementById('losses-chart');
    if (!ctx || !lossesData) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Vaisseaux', 'Points de structure', 'Valeur'],
            datasets: [
                {
                    label: 'Forces alliées',
                    data: [lossesData.allies.ships, lossesData.allies.hp, lossesData.allies.value],
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Forces ennemies',
                    data: [lossesData.enemies.ships, lossesData.enemies.hp, lossesData.enemies.value],
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#e9ecef'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#e9ecef'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#e9ecef'
                    }
                }
            }
        }
    });
}

/**
 * Crée un graphique montrant la composition des forces
 */
function createForcesCompositionChart(forcesData) {
    const ctx = document.getElementById('forces-composition-chart');
    if (!ctx || !forcesData) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: forcesData.labels,
            datasets: [
                {
                    label: 'Forces alliées',
                    data: forcesData.allies,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(13, 202, 240, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(255, 193, 7, 0.7)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(13, 202, 240, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#e9ecef'
                    }
                }
            }
        }
    });
    
    const ctxEnemies = document.getElementById('enemy-forces-composition-chart');
    if (!ctxEnemies) return;
    
    new Chart(ctxEnemies, {
        type: 'doughnut',
        data: {
            labels: forcesData.labels,
            datasets: [
                {
                    label: 'Forces ennemies',
                    data: forcesData.enemies,
                    backgroundColor: [
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(253, 126, 20, 0.7)',
                        'rgba(108, 117, 125, 0.7)',
                        'rgba(173, 181, 189, 0.7)'
                    ],
                    borderColor: [
                        'rgba(220, 53, 69, 1)',
                        'rgba(253, 126, 20, 1)',
                        'rgba(108, 117, 125, 1)',
                        'rgba(173, 181, 189, 1)'
                    ],
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#e9ecef'
                    }
                }
            }
        }
    });
}

/**
 * Crée un graphique montrant l'évolution du combat au fil des rounds
 */
function createCombatProgressionChart(progressionData) {
    const ctx = document.getElementById('combat-progression-chart');
    if (!ctx || !progressionData) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: progressionData.rounds,
            datasets: [
                {
                    label: 'Forces alliées',
                    data: progressionData.allies,
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Forces ennemies',
                    data: progressionData.enemies,
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#e9ecef'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#e9ecef'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    },
                    ticks: {
                        color: '#e9ecef'
                    }
                }
            }
        }
    });
}

/**
 * Marque le rapport comme lu via AJAX
 */
function markReportAsRead() {
    const reportId = document.querySelector('[data-report-id]')?.dataset.reportId;
    
    if (!reportId) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/game/reports/mark_read/combat/${reportId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    });
}
