# Océane – Plan d’exécution pour remettre le jeu jouable

Ce document décrit le plan d’action, les décisions validées, les jalons, et les impacts techniques. Il est conçu pour permettre de reprendre le développement même depuis une nouvelle conversation.

Dernière mise à jour: 2025-08-18

## Décisions clés (politiques et techniques)

- [x] Repo Git: init à la racine, commits fréquents par tâche (convention: `feat|fix|chore|docs|test`).
- [x] Résolution de tour transactionnelle: `GameState::lockForUpdate()` dans `GameCycleManager::executeTurn()` (déjà en place).
- [x] Inscriptions/désinscriptions:
  - Auto-approbation à la résolution du tour.
  - Le MJ peut bloquer une demande.
  - Fenêtre de gel: 60 minutes avant l’heure prévue du tour, on bloque la création de nouvelles demandes d’inscription.
  - Politique de départ par défaut: `neutral_takeover` (transfert au commandant « Neutre »).
- [ ] Combat reports: conserver la source de vérité via `GameEvent`; ajouter plus tard un pivot `combat_report_recipients` pour suivi lu/non‑lu par commandant (alignement UI/Controller).
- [ ] `Directive` modèle/table minimale ou simplification (à arbitrer plus tard).
- [ ] Mode de stack: vérifier `config('oceane.fleet.stacking_mode')` et s’assurer que `FleetManager` gère les deux modes `stack_and_row` | `stack_only`.
 - [x] Suivi lecture événements: pivot `game_event_reads` + modèle `GameEventRead`, routes Blade/JS corrigées (listes/filtrage/marquer lu).

## Contexte du code actuel (snapshot)

- Services: `GameCycleManager`, `EconomyManager`, `PopulationStabilityManager`, `FleetManager`, `CommandantManager`, `TechnologyManager`.
- Modèles: `Fleet`, `FleetShipStack`, `Ship`, `ShipDesign`, `GameEvent`, `Hero`, `StarSystem`, `Planet`, `Alliance`, etc.
- UI/Admin: Console MJ, Fleet admin CRUD, vues de base côté joueur (dashboard, systèmes, rapports).
- Concurrency: transactions + `lockForUpdate` sur entités critiques (flottes/ships/stacks/game_state).
- Base de données: MySQL/MariaDB (migrations adaptées). `FleetCargo` présent. `commander_technologies` OK.

## Principes transverses

- Concurrence: toutes les mutations de tour dans une transaction unique verrouillant `GameState` et les lignes impactées.
- Visibilité/Public vs Privé: produire une matrice après audit du jeu d’origine `C:\Users\cgibo\Documents\Projets\Océane Jeu\o2\o2`.
- UX: une page carte galaxie avec modales pour les actions; UI MJ avec vue complète (sans fog of war).
- Observabilité: `GameEvent` exhaustif; logs; tests de fumée pour les mécanismes critiques.

---

# Roadmap par phases

## Phase 1 — Inscriptions / Désinscriptions (PRIORITÉ)

Objectif: un joueur connecté peut demander à rejoindre; traitement au prochain tour. Désinscriptions idem (joueur ou MJ).

Statut: TERMINÉ.

Livré:
- Migrations: `game_state.next_turn_at`, `game_enrollments`.
- Modèle: `App\Models\GameEnrollment`.
- Service: `App\Services\EnrollmentManager` (join/leave/block/process + neutral_takeover).
- Intégration: `EnrollmentManager::processPending()` appelé depuis `GameCycleManager::executeTurn()` sous verrou.
- Config: `config/oceane.php` (flags d’inscription, freeze window, leave_policy, etc.).
- UI Joueur: formulaires Join/Leave sur le dashboard Inertia `resources/js/Pages/Dashboard.vue` (avec validations et messages d’état).
- UI MJ: liste des demandes + actions blocage/déblocage.
- Tests: features d’inscription (join/leave/block/unblock, neutral transfer). Attention: toujours fournir `race_id`; utiliser `RaceSeeder`.
- Suivi lecture événements: routes/views/JS synchronisés, modèle `GameEventRead` ajouté, pivot `game_event_reads` validé.
- Navigation: bouton “Accéder au jeu” ajouté sur le dashboard utilisateur (Inertia) vers `route('game.dashboard')`.
- Seeds: `RaceSeeder` remis aux races d’origine (noms, couleurs, poids, bonus, atmosphère, code départ).

À vérifier/ajuster rapidement:
- Certaines migrations utilisant `information_schema` doivent être contournées sous SQLite (tests).
- Vérifier la cohérence route/méthode pour l’accès système stellaire (ex: nom de route `game.star_system` vs méthode du contrôleur).
- Exécuter `php artisan db:seed --class=RaceSeeder` si nécessaire en local/tests.

### Règles métier

- Demande d’inscription possible si hors fenêtre gel (60 min avant `next_turn_at`).
- Auto-approbation à la résolution du tour, sauf blocage MJ.
- Désinscription: traitement à la résolution du tour avec politique `neutral_takeover` (transfert des actifs au « Neutre ») par défaut.

### Schéma & Migrations

- Table `game_enrollments`:
  - `id`, `user_id` FK, `type` enum [`join`, `leave`], `status` enum [`pending`, `approved`, `rejected`, `canceled`, `blocked`],
  - `payload` JSON (race, nom de commandant, préférences), `requested_at`, `processed_at`, `processed_by` (MJ), timestamps.
  - Index: `(status, type)`, `user_id`.
- `game_state` (si manquant): ajouter `next_turn_at` (datetime) pour horaire prévisionnel de résolution.
- `config/oceane.php`: `enrollment.freeze_minutes = 60`, `enrollment.auto_approve = true`, `enrollment.require_mj_approval = false`, `enrollment.allow_mj_block = true`, `enrollment.leave_policy = 'neutral_takeover'`.

### Modèle

- `App\Models\GameEnrollment` relations: `user()`, éventuellement `processedBy()` (User/MJ).

### Service

- `App\Services\EnrollmentManager`:
  - `requestJoin(User $user, array $data)` — validation (pas de pending join actif, pas de commandant actif); respect de la fenêtre gel; crée `pending`.
  - `requestLeave(User $user)` — idem pour `leave`.
  - `blockEnrollment(GameEnrollment $e, User $gm, string $reason)` — passe `blocked`.
  - `processPending(GameState $state)` — appelé depuis `executeTurn()` sous lock:
    - Pour `join`: si non bloqué -> crée `Commander` via `CommandantManager` (héros, système, flotte de départ), `approved`, `processed_at`, `GameEvent player_joined` (visibilité configurable).
    - Pour `leave`: applique politique `neutral_takeover` (transfert au « Neutre »), annule ordres, `approved`, `GameEvent player_left`.
  - Helpers: `isWithinFreezeWindow(GameState $state)`, `ensureNeutralCommander()` (ou récupération par clé/config).

### Controllers & Routes

- Joueur (`routes/game.php`, middleware `auth`):
  - `GET /game/enrollment` — formulaire ou statut.
  - `POST /game/enrollment/join` — crée demande join.
  - `POST /game/enrollment/leave` — crée demande leave.
- MJ (`routes/web.php` sous `gm`):
  - `GET /admin/enrollments` — liste `pending/blocked`.
  - Actions: `POST /admin/enrollments/{id}/block|approve|reject|cancel`.

### Vues (modales)

- Joueur: modale de demande (race, nom commandant), écran statut (`pending/approved/rejected/blocked`). CTA sur `resources/views/game/dashboard.blade.php`.
- MJ: table de suivi, filtres, actions.

### Intégration au tour

- Dans `GameCycleManager::executeTurn()` (sous `GameState::lockForUpdate()`):
  - `EnrollmentManager->processPending($state);`

### Événements

- `GameEvent` (`player_joined`, `player_left`), `turn_number`, `involved_commanders`, `visibility` (configurable: public vs mj_only). Données minimales pour audit.

### Tests

- Feature tests:
  - Join request crée `pending`, gel respecté.
  - Turn: `processPending` -> création `Commander`, événements, statut `approved`.
  - Leave: transfert au Neutre, annulation d’ordres, `approved`.
  - Permissions MJ (blocage) et joueur.

### Commits attendus

- `docs: add PLAN.md (roadmap + enrollment design)`
- `feat(enrollment): migrations + model GameEnrollment`
- `feat(enrollment): EnrollmentManager + config flags`
- `feat(enrollment): controllers + routes + views (player/admin)`
- `feat(turn): integrate enrollment processing in executeTurn()`
- `test(enrollment): basic E2E`

---

## Phase 2 — Carte de la galaxie & Fog of War

- Carte interactive (page unique) + modales pour actions.
- Détection par scanners planétaires (bâtiments) et spatiaux (composants vaisseaux).
- Afficher: système de départ, voisins, flottes alliées/ennemies/neutres selon visibilité.
- Configurer et optimiser les requêtes (eager‑loading, caches par tour si besoin).
- MJ: carte complète, pas de fog.

Statut: Partiellement livré (API JSON + tests passés; UI placeholder OK).

Livré:
- Service `App\Services\VisibilityService::buildMapData()` (filtrage visibilité, tailles flottes, galaxies visibles).
- Endpoint `/game/api/map` via `GameController@mapApi` avec mode admin et impersonation.
- Config: `oceane.commander.base_scan_range` surchargé via `.env`; `oceane.technology.sensors_bonus_per_level` appliqué.
- Factory: `database/factories/FleetFactory.php` dérive `galaxy_id` du système courant.
- Tests: `tests/Feature/GalaxyMapApiTest.php` — PASS (player/admin/impersonation).

## Phase 3 — Détails d’un système

- Vue globale (infos générales) + vue détaillée (planètes, productions, stocks, file de construction).
- Accès aux infos limité pour systèmes non possédés (se référer au jeu d’origine `o2\o2`).
- Voir la composition de ses flottes; infos agrégées limitées pour ennemies.
- MJ: vue complète.

## Phase 4 — Données de base & Seeds

- CRUD MJ pour composants, bâtiments, plans de vaisseau.
- Seeds fidèles extraits du jeu d’origine (bâtiments: mine, usine robotique, etc.).
- Harmoniser le schéma si besoin pour compatibilité des seeds.

## Phase 5 — Ordres économie/gestion systèmes

- Construire bâtiments/vaisseaux (prérequis: chantier naval, ressources: crédits + minerai + marchandises).
- Transferts planète <-> cargos.
- Capitale, taxation, renommage, politiques.
- Moteur de tour: exécution des ordres avec contrôles et coûts.

## Phase 6 — Flottes & Combat

- UI: déplacement, division, fusion, renommage, directives (modales).
- Moteur: traitement ordres au tour, combats flotte<->flotte et flotte<->planète.
- Stacks: `stack_and_row` / `stack_only`, XP vaisseaux et héros.
- Stratégies (si présentes dans le jeu d’origine): UI + prise en compte au combat.
- Production à bord optionnalisable (toggle), par défaut non forcée.

## Phase 7 — Diplomatie

- Alliances, pactes de non‑agression (UI + backend), effets sur visibilité et combats.

## Phase 8 — Missions spéciales

- Reprise fidèle des mécaniques d’origine.

## Phase 9 — Marchandises

Statut: Partiellement livré (implémentation et tests en place; stabilisation à finaliser).

Livré:
- Migrations: `natural_goods`, `system_goods`, `populations`, ajout `stability` sur `star_systems`.
- Seeders: `GoodsSeeder`, `BonusesSeeder`, `GoodsBonusSeeder` (appelés par `DatabaseSeeder`).
- Services: `EconomyManager` (agrégation production `aggregateSystemProduction`, marché `resolveMarketTurn`), `PopulationStabilityManager` (application des bonus par système/planètes, clamp stabilité 0..100).
- Intégration: appelés depuis `GameCycleManager::executeTurn()` après l’économie et avant budgets.
- Tests: `tests/Unit/PopulationStabilityManagerTest.php` (mock EconomyManager, logs, clamp), `tests/Feature/PopulationStabilityIntegrationTest.php` (tour complet avec stocks ≥100 pour déclenchement des bonus).

À faire:
- Faire tourner la suite de tests et corriger toute lenteur liée aux migrations/seeders sous SQLite en mémoire.
- Vérifier/ajuster l’arrondi des croissances de population (floor) et le clamp de stabilité [0..100].
- Consolider le logging détaillé par système/planète (valeurs avant/après et pourcentages appliqués) et s’assurer que les assertions de logs sont stables.
- Étendre la logique vers la consommation et le lien avec constructions (bâtiments/vaisseaux) dans les ordres.
- Index/perf: index supplémentaires sur `system_goods (star_system_id, commander_id, good_id)` si volumétrie élevée (déjà unique), vérifier plans d’exécution.

---

# Items techniques transverses / backlog

- [ ] `CombatReport` + pivot `combat_report_recipients (combat_report_id, commander_id, is_read)`; maj controllers/UI.
- [ ] `Directive` modèle/table minimale (ou simplifier la relation dans `Fleet`).
- [ ] Suppressions planifiées: `FleetManager::processScheduledDeletions($state)` et intégration tour.
- [ ] Matrice Public/Privé après audit `o2\o2` (événements publics, infos visibles par rôle).
- [ ] Performance: caches par tour pour visibilité/détection, indexes DB.

---

# Références & chemins utiles

- Projet actuel: `c:/Users/cgibo/Documents/Projets/OceaneSite/Oceane/`
- Projet d’origine: `C:\Users\cgibo\Documents\Projets\Océane Jeu\o2\o2\`
- Fichiers clés:
  - `app/Services/GameCycleManager.php`
  - `app/Services/FleetManager.php`
  - `app/Services/CommandantManager.php`
  - `app/Models/GameEvent.php`, `app/Models/Fleet.php`, `app/Models/Ship.php`, `app/Models/FleetShipStack.php`, `app/Models/ShipDesign.php`
  - `routes/web.php`, `routes/game.php`, `routes/console.php`
  - `config/oceane.php`
  - `resources/views/game/*`, `resources/views/admin/*`
  - `resources/js/Pages/Dashboard.vue` (lien vers `/game`)
  - `public/js/events.js` (AJAX marquer événement lu)
  - `app/Models/GameEventRead.php` (pivot lectures d’événements)

---

# Git Workflow (rappel)

- Branches par feature (ex: `feat/enrollment`).
- Commits atomiques et descriptifs.
- PR par phase; tags à la fin des jalons majeurs.

---

# Prochaines étapes immédiates

0) Stabiliser Marchandises & Population/Stabilité (prioritaire pour terminer la Phase 9):
   - Exécuter: `composer test` ou `php artisan test` (SQLite en mémoire via `phpunit.xml`).
   - Corriger lenteurs éventuelles (migrations/seeders incompatibles SQLite, éviter requêtes `information_schema`).
   - Vérifier que `EconomyManager` applique bien les bonus à seuil de stock (≥100) et que `PopulationStabilityManager` met à jour populations et stabilité avec logs attendus.
   - Documenter valeurs de bonus par marchandise dans `README.md` ou `docs/` (source: seeders).

1) Phase 2 (carte + fog of war) — cadrage et base:
   - Définir la logique de visibilité: scanners planétaires vs scanners de flotte; portée; agrégation par tour.
   - Concevoir le modèle/DTO et les requêtes pour exposer: systèmes connus + voisins, flottes visibles, états planètes visibles.
2) Backend:
   - Endpoints/contrôleurs pour fournir la carte (JSON) par tour/commandeur.
   - Optimiser avec eager‑loading et index nécessaires; prévoir caches par tour si utile.
3) UI:
   - Squelette “carte galaxie” (page unique) avec modales d’actions; vue MJ sans fog.
   - Chargement incrémental des données par cases/secteurs si volumétrie élevée.
4) Technique/Qualité:
   - Protéger les migrations non compatibles SQLite (tests) et ajouter indexes.
   - Vérifier/ajuster la route système stellaire et la méthode contrôleur correspondante.
5) Seeds:
   - S’assurer que `RaceSeeder` est exécuté; démarrer la préparation des seeds composants/bâtiments depuis le projet d’origine.
