/**
 * Gestion du centre des rapports
 * Océane 2 - Interface de rapports
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    initializeTooltips();
    
    // Initialiser le graphique d'activité
    if (typeof Chart !== 'undefined') {
        initActivityChart();
    }
    
    // Ajouter des effets d'animation aux cartes
    setupCardAnimations();
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
 * Initialise le graphique d'activité des rapports
 */
function initActivityChart() {
    const ctx = document.getElementById('reports-activity-chart');
    if (!ctx) return;
    
    try {
        const dataElement = document.getElementById('reports-data');
        if (!dataElement) return;
        
        const reportData = JSON.parse(dataElement.textContent);
        const activityData = reportData.activity;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: activityData.labels,
                datasets: [
                    {
                        label: 'Rapports de tour',
                        data: activityData.turns,
                        borderColor: 'rgba(13, 110, 253, 1)',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Combats',
                        data: activityData.combats,
                        borderColor: 'rgba(220, 53, 69, 1)',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Événements',
                        data: activityData.events,
                        borderColor: 'rgba(255, 193, 7, 1)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
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
    } catch (error) {
        console.error('Erreur lors de l\'initialisation du graphique:', error);
    }
}

/**
 * Configure les animations pour les cartes de type de rapport
 */
function setupCardAnimations() {
    const reportCards = document.querySelectorAll('.report-type-card');
    
    reportCards.forEach(card => {
        // Ajouter un effet de surbrillance au survol
        card.addEventListener('mouseenter', function() {
            const borderColor = this.classList.contains('border-primary') ? 'primary' : 
                              this.classList.contains('border-danger') ? 'danger' : 
                              this.classList.contains('border-warning') ? 'warning' : 'secondary';
            
            const icon = this.querySelector('.report-type-icon');
            if (icon) {
                icon.classList.add('animate__animated', 'animate__pulse');
                
                // Retirer l'animation après qu'elle soit terminée
                setTimeout(() => {
                    icon.classList.remove('animate__animated', 'animate__pulse');
                }, 1000);
            }
        });
        
        // Ajouter un effet de clic
        card.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
        });
        
        card.addEventListener('mouseup', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 8px 15px rgba(0, 0, 0, 0.3)';
        });
    });
    
    // Animation pour les événements récents
    animateRecentEvents();
}

/**
 * Anime l'apparition des événements récents
 */
function animateRecentEvents() {
    const eventItems = document.querySelectorAll('.list-group-item-action');
    
    // Si IntersectionObserver est disponible, utiliser pour animer au scroll
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Ajouter un délai progressif pour l'effet cascade
                    setTimeout(() => {
                        entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                        observer.unobserve(entry.target);
                    }, index * 100);
                }
            });
        }, { threshold: 0.1 });
        
        eventItems.forEach(item => {
            observer.observe(item);
        });
    } else {
        // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
        eventItems.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('animate__animated', 'animate__fadeInUp');
            }, index * 100);
        });
    }
}

/**
 * Rafraîchit les données des rapports via AJAX
 */
function refreshReportsData() {
    const refreshButton = document.getElementById('refresh-reports');
    if (!refreshButton) return;
    
    refreshButton.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-sync fa-spin"></i> Actualisation...';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/game/reports/refresh-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger la page pour afficher les données mises à jour
                window.location.reload();
            } else {
                showNotification('danger', 'Une erreur est survenue lors de l\'actualisation des données.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync"></i> Actualiser';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('danger', 'Une erreur est survenue lors de la communication avec le serveur.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-sync"></i> Actualiser';
        });
    });
}

/**
 * Affiche une notification temporaire
 * @param {string} type - Type de notification (success, danger, warning, info)
 * @param {string} message - Message à afficher
 */
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(notification, container.firstChild);
    
    // Supprimer automatiquement après 5 secondes
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 150);
    }, 5000);
}
