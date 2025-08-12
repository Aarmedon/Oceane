document.addEventListener('DOMContentLoaded', function() {
    // Récupération des données depuis la vue
    const planetData = JSON.parse(document.getElementById('planet-data').textContent);
    const planetSphere = document.getElementById('planetSphere');
    
    // Application du style en fonction du type de planète
    setupPlanetAppearance(planetSphere, planetData.planet_type);
    
    // Ajout de l'animation de rotation
    planetSphere.style.animation = "rotate 60s infinite linear";
    
    // Gestion des événements pour les bâtiments
    setupBuildingEvents();
});

/**
 * Configure l'apparence visuelle de la planète selon son type
 * 
 * @param {HTMLElement} planetElement - L'élément DOM représentant la planète
 * @param {number} planetType - Le type de planète
 */
function setupPlanetAppearance(planetElement, planetType) {
    // Définition des images de fond pour chaque type de planète
    const planetImages = {
        0: '/images/planets/terrestrial.jpg', // Terrestre
        1: '/images/planets/ocean.jpg',       // Océanique
        2: '/images/planets/desert.jpg',      // Désertique
        3: '/images/planets/jungle.jpg',      // Jungle
        4: '/images/planets/rocky.jpg',       // Rocheuse
        5: '/images/planets/gas.jpg',         // Gazeuse
        6: '/images/planets/volcanic.jpg',    // Volcanique
        7: '/images/planets/ice.jpg'          // Glaciale
    };
    
    // Fallback sur une image par défaut si le type n'est pas reconnu
    const imageUrl = planetImages[planetType] || '/images/planets/default.jpg';
    
    // Application de l'image de fond
    planetElement.style.backgroundImage = `url('${imageUrl}')`;
}

/**
 * Met en place les événements pour les cartes de bâtiments
 */
function setupBuildingEvents() {
    // Mise en surbrillance des cartes de bâtiments au survol
    const buildingCards = document.querySelectorAll('.building-card');
    buildingCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('border-primary');
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('border-primary');
        });
    });
    
    // Gestion des formulaires de construction et d'amélioration
    const buildingForms = document.querySelectorAll('form[action*="buildings"]');
    buildingForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> En cours...';
        });
    });
}

/**
 * Calcule et affiche l'estimation des revenus en fonction du taux d'imposition
 * 
 * @param {number} taxRate - Le taux d'imposition
 * @param {number} baseIncome - Le revenu de base
 */
function calculateEstimatedIncome(taxRate, baseIncome) {
    if (!taxRate || !baseIncome) return;
    
    const taxRateValue = document.getElementById('taxRateValue');
    const estimatedIncome = document.getElementById('estimatedIncome');
    
    if (!taxRateValue || !estimatedIncome) return;
    
    // Mise à jour de l'affichage du taux d'imposition
    taxRateValue.textContent = taxRate + '%';
    
    // Calcul du nouveau revenu
    const newIncome = Math.round(baseIncome * (taxRate / 100 * 2));
    estimatedIncome.textContent = newIncome.toLocaleString() + ' cr';
}

// Ajouter un élément caché contenant les données de la planète au format JSON
document.addEventListener('DOMContentLoaded', function() {
    if (!document.getElementById('planet-data')) {
        const planetDataScript = document.createElement('script');
        planetDataScript.id = 'planet-data';
        planetDataScript.type = 'application/json';
        planetDataScript.textContent = JSON.stringify(window.planetData || {});
        document.body.appendChild(planetDataScript);
    }
});
