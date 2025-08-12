# Océane – Audit & Roadmap

Date: 2025-08-10T17:52:47+11:00

## Objectif
Assurer l’intégrité transactionnelle et le contrôle de concurrence du gestionnaire de flottes (FleetManager) et livrer un moteur de combat fiable, tout en outillant l’UI admin et la base de données pour éviter les conditions de course et garantir la cohérence des données.

## Statut global
- Solide fondation transactionnelle (verrous ligne + transactions avec retries).
- Parcours combat prêt (événements et rapports) mais algorithme de résolution des rounds à implémenter.
- Quelques éléments de schéma/indices et tests de concurrence à compléter.

---

## Réalisé (Done)
- Intégrité transactionnelle et verrous au niveau ligne (lockForUpdate) dans `app/Services/FleetManager.php`:
  - `addShipToFleet()` / `addShipsToFleet()` (modes hybride et stack_only), `removeShipsFromFleet()`.
  - Mouvement de flottes, traitement des arrivées, suppressions planifiées.
  - Combat: `initiateCombat()` (création `GameEvent`, mise en statut combat), `executeCombat()` (squelette + `CombatReport`), `updateFleetsAfterCombat()` (dock/suppression et XP).
- Modèle hybride pour vaisseaux empilables (stack via `FleetShipStack`) et non-empilables (lignes `Ship`).
- Coûts d’entretien et crédits commandant mis à jour de façon atomique, garde anti-négatif.
- Événements/rapports:
  - `GameEvent` créé pour combats, `CombatReport` enregistré (participants, rounds TODO).
- Cycle de jeu et état global:
  - Remplacement de `config('oceane.game.current_turn')` par `GameState` persistant.
  - Verrou `GameState::lockForUpdate()` dans `GameCycleManager::executeTurn()`.
- Admin/MJ:
  - `ResolveTurnJob`, middleware `EnsureGameMaster` (emails depuis `config('oceane.admin.gamemasters_emails')` / `.env:GAMEMASTERS`).
  - Routes/admin vues de base; affichage Administration pour is_admin/GM.
- Schéma/migrations & modèles:
  - Tables: fleets, ships, ship_installed_components (pivot), commander_technologies (pivot), fleet_cargo, game_state.
  - Modèles minimaux: `ShipComponent`, `ShipDesign`, `FleetCargo`.
  - Enforce NOT NULL + DEFAULT pour `fleets.directive_id` y compris MariaDB.
- Correctifs récents:
  - Réparation `FleetManager::executeCombat()` (bloc corrompu/dupliqué, accolades, indentation); lint OK.
  - Correction dépréciations PHP 8.1+ (paramètres nullables explicites) dans `FleetManager` et `CommandantManager`.

---

## En cours / À faire (Backlog)
- Combat – Implémentation de l’algorithme de rounds (MVP):
  - Ciblage, dégâts, répartition sur stacks/lignes, destructions, fin de combat, vainqueur.
  - Idempotence liée au `GameEvent` et ordre de verrouillage déterministe (IDs triés) pour limiter deadlocks.
  - Alimentation complète du `CombatReport` (participants, rounds, pertes, résumé).
  
- Composants de vaisseaux:
  - Catalogue de `ShipComponent` (types: arme, défense, propulsion, support) avec stats (dégâts, cadence, portée, précision, énergie, masse) et effets.
  - Slots/points d’emport, contraintes de design (puissance dispo, masse max, compatibilité).
  - CRUD admin + seeders d’équilibrage de base; intégration au calcul combat.
  
- Conception de vaisseaux par les joueurs:
  - Éditeur `ShipDesign` (UI): sélection de coque, composants, validation (slots, puissance/masse), coûts (ressources/temps), exigences technologiques.
  - Persistance et cycle de vie (brouillon/publié), versionning simple, import/export de design.
  
- Gestion des systèmes stellaires:
  - Exploration/découverte, propriété/occupation, infrastructures et production de ressources.
  - Gouvernance locale (impôts, directives locales), routes/proximité, événements système.
  
- Gestion avancée des flottes:
  - Directives/ordres (patrouille, escorte, blocus), formations, files d’ordres, fusions/scissions.
  - Consommation (carburant/supplies) si activée via config; journalisation des ordres et mouvements.
  
- Technologies:
  - Arbre de recherche, file par commandant, coûts/temps, dépendances; effets sur composants/combat/économie.
  - UI de recherche + journal des découvertes; intégration pivot `commander_technologies`.
  
- Marchandises et cargo:
  - Types de ressources, capacité cargo, transferts flotte↔système, marchés/contrats, pillage, pertes en combat.
  - UI d’échanges et de gestion de stocks; garde contre dépassements de capacité.
  
- Héros et gouverneurs:
  - Modèles, recrutement/coût, traits/skills; affectation à flotte/système; bonus, progression, mort/capture.
  - UI de gestion, événements liés (promotion, blessure, déloyauté).
  
- Rapports et présentation au joueur:
  - Écran CombatReport détaillé, résumé de tour (GameEvents), filtres/notifications, export PDF/JSON.
  - Emails/notifications optionnelles (paramétrables).
  
- Outils d’administration:
  - Dashboards, CRUD complets (races, directives, technologies, systèmes, flottes, composants), logs/audit, rollback.
  - Outils MJ (spawn, téléportation, attribution de crédits, résolution forçée), monitoring/metrics.
  
- UI générale:
  - Layout/navigation, dashboards, carte stellaire (PixiJS/Leaflet), accessibilité (a11y), responsive, i18n.
  
- Contrainte & index BDD:
  - UNIQUE sur `fleet_ship_stacks(fleet_id, ship_design_id)`.
  - Index: `ships(fleet_id, status)`, `fleets(system_id, status)`, `game_events(system_id, turn_number)`.
  - Garde/constraint anti-négatif sur `operational_count`/`damaged_count`.
  
- Tests (Unit/Feature):
  - `addShipsToFleet()` (stack_only / stack_and_row), `removeShipsFromFleet()` (stacks+lignes), combat intégration, retry transactionnel.
  - Tests UI (Dusk) pour flux critiques (ajout/retrait, combat, échanges cargo, éditeur de design).
  
- Observabilité, perf & sécurité:
  - Journaux enrichis, métriques, traces; profiling requêtes/transactions; cleanup des tâches planifiées.
  - Politiques d’accès (Policies/Gates) sur entités, rate limits admin, audit trail.

---

## Checklist (à cocher)
- [ ] Combat MVP: résolution round par round avec stacks et rapport complet.
- [ ] Ordre de verrouillage déterministe (IDs triés) partout.
- [ ] Composants vaisseaux: catalogue, slots/contraintes, CRUD admin, seeders, intégration combat.
- [ ] Éditeur de conception de vaisseaux: UI, validation, coûts, exigences techno, persistance, import/export.
- [ ] Systèmes stellaires: exploration, propriété, production, gouvernance, routes, UI carte.
- [ ] Flottes avancées: directives/ordres, formations, files d’ordres, fusions/scissions, consommation (si activée).
- [ ] Technologies: arbre, file, dépendances, effets, UI de recherche, pivot `commander_technologies`.
- [ ] Marchandises/cargo: types, capacité, transferts, marchés, pillage, UI.
- [ ] Héros/gouverneurs: modèles, recrutement, affectation, bonus, progression, UI.
- [ ] Rapports joueur: CombatReport détaillé, résumé de tour, filtres/notifications, export.
- [ ] Outils d’admin: dashboards, CRUD complets, logs/audit, rollback, outils MJ.
- [ ] UI générale: navigation, dashboards, carte stellaire, a11y, responsive, i18n.
- [ ] UNIQUE (fleet_id, ship_design_id) sur `fleet_ship_stacks`.
- [ ] Index BDD clés (ships, fleets, game_events).
- [ ] Tests `addShipsToFleet()` (stack_only / stack_and_row).
- [ ] Tests `removeShipsFromFleet()` (stack + lignes).
- [ ] Tests combat intégration + retry transactionnel.
- [ ] Observabilité/perf: métriques, traces, profiling, cleanup.
- [ ] Sécurité: Policies/Gates, rate limits admin, audit trail.
- [ ] Créer modèle/migration `Directive` et FK.
- [ ] Résoudre migrations `races` dupliquées.
- [ ] Standardiser messages d’erreur/logs.
- [ ] Ajouter CI (PHPStan/Psalm, Pest/PHPUnit, Pint) et badges.

---

## Décisions & Assomptions
- Mode de stacking par défaut: `oceane.fleet.stacking_mode = 'stack_and_row'` (support `stack_only`).
- Retries transactionnels: 3 (DB::transaction(..., 3)).
- Crédits/entretien mis à jour atomiquement dans les transactions.
- `GameState` persiste le tour courant; `executeTurn()` prend un verrou.

---

## Fichiers / zones clés
- `app/Services/FleetManager.php`: add/remove ships, mouvements, combat (init/exécution/post-traitement), suppressions planifiées.
- `app/Services/CommandantManager.php`: création de commandant.
- `app/Jobs/ResolveTurnJob.php`, `bootstrap/app.php` (middlewares alias), `config/oceane.php`, `.env.example`.
- Migrations essentielles: fleets, ships, ship_installed_components, commander_technologies, fleet_cargo, game_state, patch `fleets.directive_id`.
- Vues admin: `resources/views/admin/*.blade.php` (notamment `fleets/edit.blade.php`).

---

## Changelog
- 2025-08-10: Correctifs `executeCombat()` (parse error), corrections dépréciations PHP (paramètres nullables explicites), lint global Services OK.

---

## Périmètre détaillé par domaine

### Composants de vaisseaux
- Objectifs: définir un écosystème de composants modulaires influençant le combat et l’économie.
- Livrables: modèle `ShipComponent` enrichi, seeders, CRUD admin, contraintes (slots/puissance/masse), calculs intégrés au combat.
- Dépendances: Technologies (déblocage), Conception de vaisseaux.
- Done: composants utilisables en jeu, valides en conception, pris en compte par le moteur de combat.

### Conception de vaisseaux (joueurs)
- Objectifs: permettre aux joueurs de créer/éditer des designs valides, avec coûts et exigences techno.
- Livrables: UI éditeur, service de validation/calculeur, persistance `ShipDesign`, versionning simple, import/export.
- Dépendances: Composants, Technologies.
- Done: création/édition déployée, designs utilisables par `FleetManager` pour construction.

### Systèmes stellaires
- Objectifs: représenter la carte, la possession, la production, et les événements locaux.
- Livrables: modèle/CRUD système, UI carte, production/stockage ressources, gouvernance locale.
- Dépendances: Marchandises, Gouverneurs, Flottes.
- Done: systèmes interactifs (production/stockage), visibles et pilotables via UI.

### Flottes
- Objectifs: aller au-delà du CRUD: ordres, formations, files, consommation (optionnelle), logs.
- Livrables: directives/ordres, formation, fusion/scission, file d’ordres, journal d’exécution.
- Dépendances: Systèmes, Conception, Composants.
- Done: ordres exécutables avec feedback UI et journaux fiables.

### Technologies
- Objectifs: progression stratégique influençant conception/combat/économie.
- Livrables: arbre/dépendances, UI de recherche, file par commandant, effets.
- Dépendances: pivot `commander_technologies`, hooks dans composants/combat.
- Done: recherche fonctionnelle et impact mesurable en jeu.

### Marchandises & cargo
- Objectifs: flux de ressources cohérents (capacité, transferts, marché/pillage).
- Livrables: UI de transfert, logique de marché/contrat (MVP), garde capacité.
- Dépendances: Systèmes, Flottes.
- Done: transferts et opérations cargo fiables, traçables.

### Héros & gouverneurs
- Objectifs: donner des bonus/traits, affectables à flottes/systèmes.
- Livrables: modèles/CRUD, recrutement, affectation, progression, événements associés.
- Dépendances: Systèmes, Flottes.
- Done: effets appliqués dans les calculs (combat/économie) et visibles en UI.

### Rapports & présentation au joueur
- Objectifs: rendre l’état du jeu lisible (combats, économie, événements).
- Livrables: UI CombatReport détaillé, résumé de tour, filtres/notifications, export.
- Dépendances: GameEvent, CombatReport.
- Done: visibilité complète, navigation rapide, exports.

### Outils d’administration
- Objectifs: exploitation/équilibrage et assistance MJ.
- Livrables: dashboards, CRUD étendus, logs/audit, rollback, actions MJ, monitoring.
- Dépendances: toutes entités majeures.
- Done: opérations admin sûres, auditées, documentées.

### UI générale, perf, sécurité
- Objectifs: UX moderne, performante et sûre.
- Livrables: navigation/layouts, carte stellaire, a11y/responsive/i18n, métriques/traces, Policies/Gates.
- Dépendances: N/A (transversal).
- Done: standards UX et SRE respectés, observabilité en place.

## Prochaines actions proposées
- Option A (Gameplay): implémenter le MVP combat (rounds + stacks).
- Option B (Robustesse): contraintes BDD + tests mutation + ordre de verrouillage.
- Option C (UX Admin): améliorer `fleets/edit` avec prévisualisation et actions bulk.

Indiquez A/B/C pour prioriser, puis nous cocherons la checklist en conséquence.
