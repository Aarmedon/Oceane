/**
 * Gestion des événements de jeu
 * Océane 2 - Interface de rapports
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    initializeTooltips();
    
    // Filtrage des événements
    setupEventFiltering();
    
    // Marquer tous les événements comme lus
    setupMarkAllAsRead();
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
 * Configure le système de filtrage des événements
 */
function setupEventFiltering() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const eventItems = document.querySelectorAll('.event-item');
    const searchInput = document.getElementById('event-search');
    
    if (!filterButtons.length || !eventItems.length || !searchInput) return;
    
    // Gestionnaire de clic pour les boutons de filtre
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            filterEvents();
        });
    });
    
    // Gestionnaire d'entrée pour la recherche
    searchInput.addEventListener('input', filterEvents);
    
    // Fonction de filtrage
    function filterEvents() {
        const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
        const searchTerm = searchInput.value.toLowerCase();
        
        eventItems.forEach(item => {
            let showItem = true;
            
            // Filtrer par type de filtre actif
            if (activeFilter === 'unread') {
                showItem = item.dataset.read === 'false';
            } else if (activeFilter === 'important') {
                showItem = parseInt(item.dataset.importance) >= 4;
            } else if (activeFilter.startsWith('type-')) {
                const filterType = activeFilter.replace('type-', '');
                showItem = item.dataset.type === filterType;
            }
            
            // Filtrer par recherche
            if (showItem && searchTerm) {
                const eventTitle = item.querySelector('h5').textContent.toLowerCase();
                const eventDesc = item.querySelector('p:last-child').textContent.toLowerCase();
                showItem = eventTitle.includes(searchTerm) || eventDesc.includes(searchTerm);
            }
            
            // Afficher ou masquer l'élément
            item.style.display = showItem ? '' : 'none';
        });
        
        // Afficher un message si aucun résultat
        const visibleItems = document.querySelectorAll('.event-item[style=""]').length;
        const noResultsElement = document.getElementById('no-results');
        
        if (visibleItems === 0) {
            if (!noResultsElement) {
                const container = document.getElementById('events-container');
                const noResults = document.createElement('div');
                noResults.id = 'no-results';
                noResults.className = 'col-12';
                noResults.innerHTML = `
                    <div class="alert alert-secondary">
                        <i class="fas fa-info-circle"></i> Aucun événement ne correspond à vos critères de recherche.
                    </div>
                `;
                container.appendChild(noResults);
            }
        } else if (noResultsElement) {
            noResultsElement.remove();
        }
    }
}

/**
 * Configure le bouton pour marquer tous les événements comme lus
 */
function setupMarkAllAsRead() {
    const markAllReadBtn = document.getElementById('mark-all-read');
    
    if (!markAllReadBtn) return;
    
    markAllReadBtn.addEventListener('click', function() {
        // Désactiver le bouton pendant le traitement
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // Utiliser l'URL fournie dans l'attribut data-url ou une URL par défaut
        const url = this.dataset.url || '/game/reports/events/mark-all-read';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprimer tous les indicateurs de non-lu
                document.querySelectorAll('.event-unread').forEach(indicator => {
                    indicator.remove();
                });
                
                // Mettre à jour les attributs data-read
                document.querySelectorAll('.event-item').forEach(item => {
                    item.dataset.read = 'true';
                });
                
                // Mettre à jour le compteur de non-lus
                const unreadBadge = document.querySelector('.filter-btn[data-filter="unread"] .badge');
                if (unreadBadge) {
                    unreadBadge.textContent = '0';
                }
                
                // Afficher une notification
                showNotification('success', data.message || 'Tous les événements ont été marqués comme lus.');
                
                // Filtrer à nouveau si nécessaire
                if (document.querySelector('.filter-btn.active').dataset.filter === 'unread') {
                    const filterEvent = new Event('click');
                    document.querySelector('.filter-btn[data-filter="all"]').dispatchEvent(filterEvent);
                }
            } else {
                showNotification('danger', data.message || 'Une erreur est survenue lors du marquage des événements.');
            }
            
            // Réactiver le bouton
            const markAllReadBtn = document.getElementById('mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.disabled = false;
                markAllReadBtn.innerHTML = '<i class="fas fa-check-double"></i> Tout marquer comme lu';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('danger', 'Une erreur de communication est survenue.');
            
            // Réactiver le bouton en cas d'erreur
            const markAllReadBtn = document.getElementById('mark-all-read');
            if (markAllReadBtn) {
                markAllReadBtn.disabled = false;
                markAllReadBtn.innerHTML = '<i class="fas fa-check-double"></i> Tout marquer comme lu';
            }
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

/**
 * Marque un événement spécifique comme lu
 * Utilisé sur la page de détail d'un événement
 */
function markEventAsRead(eventId) {
    if (!eventId) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/game/reports/mark_read/event/${eventId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    });
}
