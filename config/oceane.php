<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration générale du jeu Océane
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient toutes les constantes et paramètres pour le jeu Océane
    |
    */

    'game' => [
        'name' => 'Océane',
        'version' => '1.0.0',
        'current_turn' => env('GAME_CURRENT_TURN', 1),
        'hours_between_turns' => env('HOURS_BETWEEN_TURNS', 24),
        'max_players' => env('MAX_PLAYERS', 100),
        'registration_open' => env('REGISTRATION_OPEN', true),
    ],

    // Configuration des galaxies
    'galaxy' => [
        'size_x' => 100,
        'size_y' => 100,
        'sector_count' => 25, // Nombre de secteurs par galaxie (5x5)
        'min_portals' => 2,   // Nombre minimum de passages galactiques
        'max_portals' => 4,   // Nombre maximum de passages galactiques
    ],

    // Configuration des secteurs
    'sector' => [
        'min_star_systems' => 10,
        'max_star_systems' => 20,
    ],

    // Configuration des systèmes stellaires
    'system' => [
        'min_planets' => 1,
        'max_planets' => 20,
        'default_tax_rate' => 2,
        'maintenance_cost' => 50,
    ],

    // Types d'étoiles
    'star_types' => [
        0 => 'Naine rouge',
        1 => 'Naine jaune',
        2 => 'Géante bleue',
        3 => 'Géante rouge',
        4 => 'Étoile binaire',
        5 => 'Pulsar',
    ],

    // Configuration des planètes
    'planet' => [
        'max_size' => 5,
        'max_type' => 19,
        'max_resources' => 5,
        'min_radiation' => 0,
        'max_radiation' => 200,
        'min_temperature' => -150,
        'max_temperature' => 200,
        'min_gravity' => 0,
        'max_gravity' => 100,
        'atmosphere_types' => 10,
        'habitable_types' => [0, 1, 2, 3, 4], // Indices des types habitables
        
        // Types de planètes
        'types' => [
            0 => 'Terrestre',
            1 => 'Océanique',
            2 => 'Désertique',
            3 => 'Jungle',
            4 => 'Rocheuse',
            5 => 'Gazeuse',
            6 => 'Volcanique',
            7 => 'Glaciale',
            8 => 'Toxique',
            9 => 'Radioactive',
            10 => 'Naine',
            11 => 'Géante gazeuse',
            12 => 'Planète de métal',
            13 => 'Planète cristalline',
            14 => 'Lune',
            15 => 'Planète artificielle',
            16 => 'Astéroïde',
            17 => 'Planète morte',
            18 => 'Anneau planétaire',
            19 => 'Trou noir miniature',
        ],
    ],

    // Configuration des commandants
    'commander' => [
        'starting_credits' => 1000,
        'starting_reputation' => 0,
        // Portée de scan de base (utilisée par la visibilité de la carte). Surchargable via .env
        'base_scan_range' => (int) env('COMMANDER_BASE_SCAN_RANGE', 10),
    ],

    // Configuration des héros
    'hero' => [
        'base_maintenance' => 50,
    ],

    // Configuration des flottes
    'fleet' => [
        'default_morale' => 100,
        'default_speed' => 5,
        // Mode de gestion des vaisseaux stackables: 'stack_and_row' (par défaut) ou 'stack_only'
        'stacking_mode' => env('FLEET_STACKING_MODE', 'stack_and_row'),
    ],

    // Constantes des statuts de flotte
    'fleet_status' => [
        'docked' => 0,
        'moving' => 1,
        'combat' => 2,
        'repairing' => 3,
    ],

    // Configuration des vaisseaux
    'ship' => [
        'default_speed' => 3,
    ],

    // Constantes des statuts de vaisseau
    'ship_status' => [
        'operational' => 0,
        'damaged' => 1,
        'critical' => 2,
        'repairing' => 3,
        'destroyed' => 4,
    ],

    // Configuration des directives de flotte
    'directives' => [
        'patrol' => 0,
        'attack_system' => 1,
        'defend_system' => 2,
        'attack_fleets' => 3,
        'pillage_system' => 4,
        'attack_planet' => 5,
        'pillage_planet' => 6,
        'eradicate_planet' => 7,
        'attack_player' => 8,
        'escort' => 9,
    ],

    // Configuration des voyages
    'travel' => [
        'intergalactic_multiplier' => 2,
    ],

    // Configuration des technologies
    'technology' => [
        'base_research_time' => 3,
        // Bonus de portée des capteurs par niveau de la technologie "sensors"
        // (utilisé pour la visibilité de la carte)
        'sensors_bonus_per_level' => 1,
    ],

    // Configuration des combats
    'combat' => [
        'max_rounds' => 10,
        'experience_gain' => 10,
    ],

    // Configuration des alliances
    'alliance' => [
        'anarchic_income' => 100,
    ],

    // Configuration administration (Maître du Jeu)
    'admin' => [
        // Liste d'emails autorisés à accéder à l'interface MJ (séparés par des virgules dans .env)
        'gamemasters_emails' => array_filter(array_map('trim', explode(',', env('GAMEMASTERS', '')))),
    ],

    // Configuration des inscriptions/joueurs
    'enrollment' => [
        // Fenêtre de gel avant la résolution du tour (en minutes)
        'freeze_window_minutes' => env('ENROLLMENT_FREEZE_MINUTES', 60),

        // Approbation automatique des demandes au moment de la résolution
        'auto_approve' => env('ENROLLMENT_AUTO_APPROVE', true),

        // Politique par défaut lors du départ d'un joueur
        // ex: 'neutral_takeover' (transfert au commandant neutre)
        'default_leave_policy' => env('LEAVE_POLICY', 'neutral_takeover'),

        // Autoriser les MJ à bloquer une demande
        'allow_gm_block' => env('ENROLLMENT_ALLOW_GM_BLOCK', true),

        // Paramètres du commandant neutre
        'neutral' => [
            'user_email' => env('NEUTRAL_USER_EMAIL', 'neutral@oceane.local'),
            'user_name' => env('NEUTRAL_USER_NAME', 'Neutral'),
            'commander_name' => env('NEUTRAL_COMMANDER_NAME', 'Neutral'),
            'race_id' => (int) env('NEUTRAL_RACE_ID', 1),
        ],
    ],
];
