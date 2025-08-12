/**
 * Gestion des rapports de tour
 * Océane 2 - Interface de rapports
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    initializeTooltips();
    
    // Initialiser les graphiques si la bibliothèque Chart.js est disponible
    if (typeof Chart !== 'undefined') {
        initializeTurnReportCharts();
    }
    
    // Configurer les onglets et accordéons
    setupTabsAndAccordions();
    
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
 * Configure les interactions pour les onglets et accordéons
 */
function setupTabsAndAccordions() {
    // Gestion des accordéons pour les activités détaillées
    const accordionHeaders = document.querySelectorAll('.activity-header');
    
    accordionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const isCollapsed = content.classList.contains('show');
            const icon = this.querySelector('.activity-toggle i');
            
            if (isCollapsed) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        });
    });
    
    // Gestion des onglets pour les différentes sections du rapport
    const reportTabs = document.querySelectorAll('[data-bs-toggle="tab"]');
    
    reportTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(event) {
            // Redimensionner les graphiques si nécessaire quand un onglet est affiché
            if (typeof Chart !== 'undefined') {
                const targetId = event.target.getAttribute('href');
                const charts = document.querySelectorAll(`${targetId} canvas`);
                
                charts.forEach(canvas => {
                    const chartInstance = Chart.getChart(canvas);
                    if (chartInstance) {
                        chartInstance.resize();
                    }
                });
            }
        });
    });
}

/**
 * Initialise les graphiques pour visualiser les données du rapport de tour
 */
function initializeTurnReportCharts() {
    // Récupérer les données JSON si disponibles
    const dataElement = document.getElementById('report-data');
    if (!dataElement) return;
    
    try {
        const reportData = JSON.parse(dataElement.textContent);
        
        // Graphique des ressources
        createResourcesChart(reportData.resources);
        
        // Graphique de l'évolution de la population
        createPopulationChart(reportData.population);
        
        // Graphique des activités par catégorie
        createActivitiesChart(reportData.activities);
        
        // Graphique des revenus et dépenses
        createFinancialChart(reportData.financial);
    } catch (error) {
        console.error('Erreur lors de l\'initialisation des graphiques:', error);
    }
}

/**
 * Crée un graphique montrant l'évolution des ressources
 */
function createResourcesChart(resourcesData) {
    const ctx = document.getElementById('resources-chart');
    if (!ctx || !resourcesData) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Minéraux', 'Gaz', 'Crédits', 'Énergie'],
            datasets: [
                {
                    label: 'Début du tour',
                    data: [
                        resourcesData.start.minerals,
                        resourcesData.start.gas,
                        resourcesData.start.credits,
                        resourcesData.start.energy
                    ],
                    backgroundColor: 'rgba(13, 202, 240, 0.7)',
                    borderColor: 'rgba(13, 202, 240, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Fin du tour',
                    data: [
                        resourcesData.end.minerals,
                        resourcesData.end.gas,
                        resourcesData.end.credits,
                        resourcesData.end.energy
                    ],
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
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
 * Crée un graphique montrant l'évolution de la population
 */
function createPopulationChart(populationData) {
    const ctx = document.getElementById('population-chart');
    if (!ctx || !populationData) return;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: populationData.turns,
            datasets: [
                {
                    label: 'Population totale',
                    data: populationData.total,
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Croissance',
                    data: populationData.growth,
                    borderColor: 'rgba(25, 135, 84, 1)',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
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
                    beginAtZero: false,
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
 * Crée un graphique montrant la répartition des activités
 */
function createActivitiesChart(activitiesData) {
    const ctx = document.getElementById('activities-chart');
    if (!ctx || !activitiesData) return;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: activitiesData.labels,
            datasets: [
                {
                    data: activitiesData.values,
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(13, 202, 240, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: [
                        'rgba(13, 110, 253, 1)',
                        'rgba(25, 135, 84, 1)',
                        'rgba(13, 202, 240, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(108, 117, 125, 1)'
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
 * Crée un graphique montrant les revenus et dépenses
 */
function createFinancialChart(financialData) {
    const ctx = document.getElementById('financial-chart');
    if (!ctx || !financialData) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: financialData.categories,
            datasets: [
                {
                    label: 'Revenus',
                    data: financialData.income,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Dépenses',
                    data: financialData.expenses,
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
 * Marque le rapport comme lu via AJAX
 */
function markReportAsRead() {
    const reportId = document.querySelector('[data-report-id]')?.dataset.reportId;
    
    if (!reportId) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/game/reports/mark_read/turn/${reportId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    });
}
