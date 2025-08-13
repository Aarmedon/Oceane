<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TurnReport;
use App\Models\CombatReport;
use App\Models\GameEvent;
use App\Models\GameEventRead;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDF;

class ReportController extends Controller
{
    /**
     * Affiche la page d'index centralisée des rapports
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $commander = Auth::user()->commander;
        
        // Récupérer les statistiques des rapports
        $turnReportsCount = $commander->turnReports()->count();
        $combatReportsCount = $commander->combatReports()->count();
        $eventsCount = $commander->gameEvents()->count();
        
        // Récupérer les rapports non lus
        $unreadTurnReports = $commander->turnReports()->where('is_read', false)->count();
        $unreadCombatReports = $commander->combatReports()->where('is_read', false)->count();
        $unreadEvents = $commander->gameEvents()
            ->whereDoesntHave('reads', function ($q) use ($commander) {
                $q->where('commander_id', $commander->id);
            })
            ->count();
        
        // Récupérer les événements importants (importance explicite ou importance par défaut du type)
        $importantEventsCount = $commander->gameEvents()
            ->where(function ($q) {
                $q->where('event_data->importance', '>=', 4)
                  ->orWhereIn('event_type', ['space_combat', 'planet_colonization']);
            })
            ->count();
        
        // Récupérer les derniers rapports pour afficher leur date
        $lastTurnReport = $commander->turnReports()->latest('created_at')->first();
        $lastCombatReport = $commander->combatReports()->latest('created_at')->first();
        $lastEvent = $commander->gameEvents()->latest('created_at')->first();
        
        // Récupérer les événements récents pour l'affichage rapide
        $recentEvents = $commander->gameEvents()
            ->with(['reads' => function ($q) use ($commander) { $q->where('commander_id', $commander->id); }])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Préparer les données pour le graphique d'activité
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        // Générer les labels pour les 30 derniers jours
        $activityLabels = [];
        $activityTurns = [];
        $activityCombats = [];
        $activityEvents = [];
        
        $currentDate = clone $startDate;
        while ($currentDate <= $endDate) {
            $dateString = $currentDate->format('d/m');
            $activityLabels[] = $dateString;
            
            // Compter les rapports pour cette date
            $dayStart = clone $currentDate;
            $dayEnd = (clone $currentDate)->endOfDay();
            
            $activityTurns[] = $commander->turnReports()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
                
            $activityCombats[] = $commander->combatReports()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
                
            $activityEvents[] = $commander->gameEvents()
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();
            
            $currentDate->addDay();
        }
        
        return view('game.reports.index', compact(
            'turnReportsCount', 
            'combatReportsCount', 
            'eventsCount',
            'unreadTurnReports',
            'unreadCombatReports',
            'unreadEvents',
            'importantEventsCount',
            'lastTurnReport',
            'lastCombatReport',
            'lastEvent',
            'recentEvents',
            'activityLabels',
            'activityTurns',
            'activityCombats',
            'activityEvents'
        ));
    }
    
    /**
     * Rafraîchit les données des rapports via AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshData()
    {
        try {
            // Cette méthode pourrait être utilisée pour forcer une mise à jour des données
            // Par exemple, vérifier s'il y a de nouveaux rapports depuis la dernière vérification
            
            return response()->json([
                'success' => true,
                'message' => 'Données actualisées avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'actualisation des données des rapports: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'actualisation des données'
            ], 500);
        }
    }
    /**
     * Affiche un rapport de tour spécifique
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showTurnReport($id)
    {
        $commander = Auth::user()->commander;
        $report = TurnReport::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        // Préparer les données pour la vue
        $reportData = [
            'report' => $report
        ];
        
        return view('game.reports.turn', compact('report', 'reportData'));
    }
    
    /**
     * Affiche un rapport de combat spécifique
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showCombatReport($id)
    {
        $commander = Auth::user()->commander;
        $report = CombatReport::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        // Préparer les données pour la vue
        $combatReportData = [
            'report' => $report
        ];
        
        return view('game.reports.combat', compact('report', 'combatReportData'));
    }
    
    /**
     * Affiche un événement de jeu spécifique
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showGameEvent($id)
    {
        $commander = Auth::user()->commander;
        $event = $commander->gameEvents()
            ->with(['reads' => function ($q) use ($commander) { $q->where('commander_id', $commander->id); }])
            ->where('id', $id)
            ->firstOrFail();
        
        return view('game.reports.event', compact('event'));
    }
    
    /**
     * Marque un rapport de tour comme lu
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markTurnReportAsRead($id)
    {
        $commander = Auth::user()->commander;
        $report = TurnReport::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        $report->is_read = true;
        $report->save();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Marque un rapport de combat comme lu
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markCombatReportAsRead($id)
    {
        $commander = Auth::user()->commander;
        $report = CombatReport::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        $report->is_read = true;
        $report->save();
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Marque un événement de jeu comme lu
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markGameEventAsRead($id)
    {
        $commander = Auth::user()->commander;
        // Vérifier que l'événement concerne bien ce commandant
        $event = $commander->gameEvents()->where('id', $id)->firstOrFail();
        
        // Créer l'enregistrement de lecture si non existant
        GameEventRead::firstOrCreate(
            ['game_event_id' => $event->id, 'commander_id' => $commander->id],
            ['read_at' => now()]
        );
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Télécharge un rapport de tour au format PDF
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function downloadTurnReport($id)
    {
        $commander = Auth::user()->commander;
        $report = TurnReport::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        // Marquer comme lu
        $report->is_read = true;
        $report->save();
        
        // Générer le PDF
        $pdf = PDF::loadView('game.reports.pdf.turn', compact('report'));
        
        return $pdf->download('rapport_tour_' . $report->turn_number . '.pdf');
    }
    
    /**
     * Télécharge un rapport de combat au format PDF
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function downloadCombatReport($id)
    {
        $commander = Auth::user()->commander;
        $report = CombatReport::where('id', $id)
            ->where('commander_id', $commander->id)
            ->firstOrFail();
        
        // Marquer comme lu
        $report->is_read = true;
        $report->save();
        
        // Générer le PDF
        $pdf = PDF::loadView('game.reports.pdf.combat', compact('report'));
        
        return $pdf->download('rapport_combat_' . $report->id . '.pdf');
    }
    
    /**
     * Liste tous les rapports de tour du commandant avec options de filtrage
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function listTurnReports(Request $request)
    {
        $commander = Auth::user()->commander;
        $query = $commander->turnReports();
        
        // Filtres
        if ($request->has('unread') && $request->unread == 1) {
            $query->where('is_read', false);
        }
        
        if ($request->has('turn_number')) {
            $query->where('turn_number', $request->turn_number);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Tri
        $sortField = $request->get('sort', 'turn_number');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $reports = $query->paginate(15)->appends($request->query());
        
        // Statistiques pour la vue
        $stats = [
            'total' => $commander->turnReports()->count(),
            'unread' => $commander->turnReports()->where('is_read', false)->count(),
        ];
        
        return view('game.reports.turns', compact('reports', 'stats'));
    }
    
    /**
     * Liste tous les rapports de combat du commandant avec options de filtrage
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function listCombatReports(Request $request)
    {
        $commander = Auth::user()->commander;
        $query = $commander->combatReports();
        
        // Filtres
        if ($request->has('unread') && $request->unread == 1) {
            $query->where('is_read', false);
        }
        
        if ($request->has('turn_number')) {
            $query->where('turn_number', $request->turn_number);
        }
        
        if ($request->has('result')) {
            $query->where('result', $request->result); // victoire, défaite, etc.
        }
        
        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Tri
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $reports = $query->paginate(15)->appends($request->query());
        
        // Statistiques pour la vue
        $stats = [
            'total' => $commander->combatReports()->count(),
            'unread' => $commander->combatReports()->where('is_read', false)->count(),
            'victories' => $commander->combatReports()->where('result', 'victory')->count(),
            'defeats' => $commander->combatReports()->where('result', 'defeat')->count(),
        ];
        
        return view('game.reports.combats', compact('reports', 'stats'));
    }
    
    /**
     * Liste tous les événements de jeu du commandant avec options de filtrage
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function listGameEvents(Request $request)
    {
        $commander = Auth::user()->commander;
        $query = $commander->gameEvents()
            ->with(['reads' => function ($q) use ($commander) { $q->where('commander_id', $commander->id); }]);
        
        // Filtres
        if ($request->has('unread') && (int) $request->unread === 1) {
            $query->whereDoesntHave('reads', function ($q) use ($commander) {
                $q->where('commander_id', $commander->id);
            });
        }
        
        if ($request->has('importance')) {
            $threshold = (int) $request->importance;
            $query->where(function ($q) use ($threshold) {
                $q->where('event_data->importance', '>=', $threshold)
                  ->orWhere(function ($q2) use ($threshold) {
                      // Importance par défaut selon le type
                      $types = [];
                      if ($threshold <= 5 && $threshold > 4) { $types = ['space_combat']; }
                      elseif ($threshold <= 4) { $types = ['space_combat', 'planet_colonization']; }
                      // Seuils inférieurs: tous les types sont >= 2 par défaut
                      if ($threshold <= 3) { $types = array_merge($types, ['technology_discovery', 'diplomatic_event']); }
                      if ($threshold <= 2) { $types = array_merge($types, ['player_joined', 'player_left']); }
                      if (!empty($types)) {
                          $q2->whereIn('event_type', array_unique($types));
                      } else {
                          // Aucun type par défaut ne correspond à ce seuil
                          $q2->whereRaw('1 = 0');
                      }
                  });
            });
        }
        
        if ($request->has('type')) {
            $query->where('event_type', $request->type);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('event_data->title', 'like', '%' . $search . '%')
                  ->orWhere('event_data->description', 'like', '%' . $search . '%')
                  ->orWhere('event_type', 'like', '%' . $search . '%');
            });
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Tri
        $requestedSort = $request->get('sort', 'created_at');
        $sortField = $requestedSort === 'type' ? 'event_type' : 'created_at';
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $events = $query->paginate(20)->appends($request->query());
        
        // Statistiques pour la vue
        $stats = [
            'total' => $commander->gameEvents()->count(),
            'unread' => $commander->gameEvents()->whereDoesntHave('reads', function ($q) use ($commander) { $q->where('commander_id', $commander->id); })->count(),
            'important' => $commander->gameEvents()->where(function ($q) {
                $q->where('event_data->importance', '>=', 4)
                  ->orWhereIn('event_type', ['space_combat', 'planet_colonization']);
            })->count(),
        ];
        
        // Types d'événements pour les filtres
        $eventTypes = $commander->gameEvents()->select('event_type')->distinct()->pluck('event_type');
        
        return view('game.reports.events', compact('events', 'stats', 'eventTypes'));
    }
    
    /**
     * Marque tous les rapports de tour comme lus
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllTurnReportsAsRead()
    {
        $commander = Auth::user()->commander;
        
        try {
            $commander->turnReports()->where('is_read', false)->update(['is_read' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tous les rapports de tour ont été marqués comme lus'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du marquage des rapports de tour comme lus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }
    
    /**
     * Marque tous les rapports de combat comme lus
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllCombatReportsAsRead()
    {
        $commander = Auth::user()->commander;
        
        try {
            $commander->combatReports()->where('is_read', false)->update(['is_read' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tous les rapports de combat ont été marqués comme lus'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du marquage des rapports de combat comme lus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }
    
    /**
     * Marque tous les événements de jeu comme lus
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllGameEventsAsRead()
    {
        $commander = Auth::user()->commander;
        
        try {
            $eventIds = $commander->gameEvents()->pluck('id');
            if ($eventIds->isNotEmpty()) {
                $now = now();
                $rows = $eventIds->map(function ($id) use ($commander, $now) {
                    return [
                        'game_event_id' => $id,
                        'commander_id' => $commander->id,
                        'read_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->toArray();
                DB::table('game_event_reads')->insertOrIgnore($rows);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Tous les événements ont été marqués comme lus'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du marquage des événements comme lus: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue'
            ], 500);
        }
    }
}
