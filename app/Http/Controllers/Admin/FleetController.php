<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commander;
use App\Models\Directive;
use App\Models\Fleet;
use App\Models\StarSystem;
use App\Models\GameState;
use Illuminate\Http\Request;
use App\Services\FleetManager;
use App\Models\ShipDesign;

class FleetController extends Controller
{
    /**
     * Display a listing of fleets with filters and pagination.
     */
    public function index(Request $request)
    {
        $query = Fleet::query()
            ->with(['commander', 'directive', 'currentSystem', 'destinationSystem', 'shipStacks.shipDesign', 'ships.shipDesign'])
            ->withCount(['ships', 'cargo']);

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($commanderId = (int) $request->get('commander_id')) {
            $query->where('commander_id', $commanderId);
        }

        if (strlen((string) $request->get('directive_id'))) {
            $query->where('directive_id', (int) $request->get('directive_id'));
        }

        if ($status = trim((string) $request->get('status'))) {
            $query->where('status', $status);
        }

        $fleets = $query->orderBy('updated_at', 'desc')->paginate(20)->withQueryString();

        // Compute compositions by stacks (with fallback) for display
        $manager = app(FleetManager::class);
        foreach ($fleets as $fleet) {
            // Attach a non-persistent attribute for the view
            $fleet->composition = $manager->getFleetComposition($fleet);
        }

        $commanders = Commander::orderBy('name')->get(['id', 'name']);
        $directives = Directive::orderBy('name')->get(['id', 'name']);

        // Optional: statuses list from constants
        $statuses = [
            Fleet::STATUS_DOCKED => 'Docked',
            Fleet::STATUS_MOVING => 'Moving',
            Fleet::STATUS_COMBAT => 'Combat',
            Fleet::STATUS_WAITING => 'Waiting',
        ];

        return view('admin.fleets.index', compact('fleets', 'commanders', 'directives', 'statuses'));
    }

    /**
     * Show the form for creating a new fleet.
     */
    public function create()
    {
        $commanders = Commander::orderBy('name')->get(['id', 'name']);
        $systems = StarSystem::orderBy('name')->get(['id', 'name']);
        $directives = Directive::orderBy('name')->get(['id', 'name']);
        $statuses = [
            Fleet::STATUS_DOCKED => 'Docked',
            Fleet::STATUS_MOVING => 'Moving',
            Fleet::STATUS_COMBAT => 'Combat',
            Fleet::STATUS_WAITING => 'Waiting',
        ];
        return view('admin.fleets.create', compact('commanders', 'systems', 'directives', 'statuses'));
    }

    /**
     * Store a newly created fleet in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'commander_id' => ['required', 'integer', 'exists:commanders,id'],
            'current_system_id' => ['required', 'integer', 'exists:star_systems,id'],
            'directive_id' => ['required', 'integer', 'exists:directives,id'],
            'status' => ['nullable', 'in:'.implode(',', [
                Fleet::STATUS_DOCKED,
                Fleet::STATUS_MOVING,
                Fleet::STATUS_COMBAT,
                Fleet::STATUS_WAITING,
            ])],
        ]);

        $system = StarSystem::findOrFail($data['current_system_id']);

        $fleet = Fleet::create([
            'name' => $data['name'],
            'commander_id' => $data['commander_id'],
            'current_system_id' => $system->id,
            'destination_system_id' => null,
            'position_x' => $system->position_x,
            'position_y' => $system->position_y,
            'galaxy_id' => optional($system->sector)->galaxy_id,
            'status' => $data['status'] ?? Fleet::STATUS_DOCKED,
            'arrival_turn' => null,
            'directive_id' => $data['directive_id'],
            'hero_id' => null,
            'morale' => config('oceane.fleet.default_morale', 100),
            'experience' => 0,
            'maintenance_cost' => 0,
        ]);

        return redirect()->route('admin.fleets.index')
            ->with('success', "Flotte {$fleet->name} créée.");
    }

    /**
     * Show the form for editing the specified fleet.
     */
    public function edit(Fleet $fleet)
    {
        $commanders = Commander::orderBy('name')->get(['id', 'name']);
        $systems = StarSystem::orderBy('name')->get(['id', 'name']);
        $directives = Directive::orderBy('name')->get(['id', 'name']);
        $shipDesigns = ShipDesign::orderBy('name')->get(['id','name','is_stackable']);
        $statuses = [
            Fleet::STATUS_DOCKED => 'Docked',
            Fleet::STATUS_MOVING => 'Moving',
            Fleet::STATUS_COMBAT => 'Combat',
            Fleet::STATUS_WAITING => 'Waiting',
        ];
        // Compute composition for read-only display
        $composition = app(FleetManager::class)->getFleetComposition($fleet);
        return view('admin.fleets.edit', compact('fleet', 'commanders', 'systems', 'directives', 'statuses', 'composition', 'shipDesigns'));
    }

    /**
     * Add ships to fleet composition.
     */
    public function addComposition(Request $request, Fleet $fleet)
    {
        $data = $request->validate([
            'ship_design_id' => ['required','integer','exists:ship_designs,id'],
            'count' => ['required','integer','min:1','max:100000'],
            'base_name' => ['nullable','string','max:255'],
        ]);

        $design = ShipDesign::findOrFail($data['ship_design_id']);
        try {
            // En contexte admin (GM), on bypass les contraintes de crédits
            app(FleetManager::class)->addShipsToFleet($fleet, $design, (int)$data['count'], $data['base_name'] ?? null, true);
        } catch (\Throwable $e) {
            return redirect()->route('admin.fleets.edit', $fleet)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.fleets.edit', $fleet)
            ->with('success', "{$data['count']} {$design->name} ajoutés à la flotte.");
    }

    /**
     * Remove ships from fleet composition.
     */
    public function removeComposition(Request $request, Fleet $fleet)
    {
        $data = $request->validate([
            'ship_design_id' => ['required','integer','exists:ship_designs,id'],
            'count' => ['required','integer','min:1','max:100000'],
        ]);

        $design = ShipDesign::findOrFail($data['ship_design_id']);
        try {
            app(FleetManager::class)->removeShipsFromFleet($fleet, $design, (int)$data['count']);
        } catch (\Throwable $e) {
            return redirect()->route('admin.fleets.edit', $fleet)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.fleets.edit', $fleet)
            ->with('success', "{$data['count']} {$design->name} retirés de la flotte.");
    }

    /**
     * Update the specified fleet in storage.
     */
    public function update(Request $request, Fleet $fleet)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'commander_id' => ['required', 'integer', 'exists:commanders,id'],
            'current_system_id' => ['required', 'integer', 'exists:star_systems,id'],
            'directive_id' => ['required', 'integer', 'exists:directives,id'],
            'status' => ['required', 'in:'.implode(',', [
                Fleet::STATUS_DOCKED,
                Fleet::STATUS_MOVING,
                Fleet::STATUS_COMBAT,
                Fleet::STATUS_WAITING,
            ])],
        ]);

        $system = StarSystem::findOrFail($data['current_system_id']);

        $fleet->update([
            'name' => $data['name'],
            'commander_id' => $data['commander_id'],
            'current_system_id' => $system->id,
            'position_x' => $system->position_x,
            'position_y' => $system->position_y,
            'galaxy_id' => optional($system->sector)->galaxy_id,
            'directive_id' => $data['directive_id'],
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.fleets.index')
            ->with('success', "Flotte {$fleet->name} mise à jour.");
    }

    /**
     * Schedule the specified fleet for deletion at the next turn resolution.
     */
    public function destroy(Fleet $fleet)
    {
        $currentTurn = (int) (GameState::query()->value('current_turn') ?? 1);

        $fleet->update([
            'scheduled_for_deletion' => true,
            'scheduled_deletion_turn' => $currentTurn + 1,
        ]);

        return redirect()->route('admin.fleets.index')
            ->with('success', "Suppression planifiée pour la flotte {$fleet->name} (tour ".($currentTurn + 1).")");
    }

    /**
     * Cancel scheduled deletion for a fleet.
     */
    public function cancelDeletion(Fleet $fleet)
    {
        $fleet->update([
            'scheduled_for_deletion' => false,
            'scheduled_deletion_turn' => null,
        ]);

        return redirect()->route('admin.fleets.index')
            ->with('success', "Suppression planifiée annulée pour la flotte {$fleet->name}.");
    }
}
