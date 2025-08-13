@extends('layouts.app')

@section('title', 'Centre de Commandement')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/orders.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Centre de Commandement</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <!-- Colonne de gauche : Ordres -->
        <div class="col-md-7 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-tasks"></i> Ordres</h4>
                    <div>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                            <i class="fas fa-plus"></i> Nouvel ordre
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="ordersTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                En attente <span class="badge bg-primary">{{ $pendingOrders->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="processed-tab" data-bs-toggle="tab" data-bs-target="#processed" type="button" role="tab">
                                Traités <span class="badge bg-success">{{ $processedOrders->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="failed-tab" data-bs-toggle="tab" data-bs-target="#failed" type="button" role="tab">
                                Échoués <span class="badge bg-danger">{{ $failedOrders->count() }}</span>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3" id="ordersTabContent">
                        <!-- Ordres en attente -->
                        <div class="tab-pane fade show active" id="pending" role="tabpanel">
                            @if($pendingOrders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Détails</th>
                                                <th>Tour</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingOrders as $order)
                                                <tr>
                                                    <td>
                                                        @switch($order->order_type)
                                                            @case('move')
                                                                <span class="badge bg-info"><i class="fas fa-route"></i> Déplacement</span>
                                                                @break
                                                            @case('colonize')
                                                                <span class="badge bg-success"><i class="fas fa-flag"></i> Colonisation</span>
                                                                @break
                                                            @case('research')
                                                                <span class="badge bg-primary"><i class="fas fa-flask"></i> Recherche</span>
                                                                @break
                                                            @case('build')
                                                                <span class="badge bg-warning text-dark"><i class="fas fa-hammer"></i> Construction</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary"><i class="fas fa-cog"></i> {{ ucfirst($order->order_type) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-link order-details-btn" data-order-id="{{ $order->id }}">
                                                            {{ $order->getShortDescription() }}
                                                        </button>
                                                    </td>
                                                    <td>
                                                        @if($order->turn_execution > $currentTurn)
                                                            <span class="badge bg-info">Tour {{ $order->turn_execution }}</span>
                                                        @else
                                                            <span class="badge bg-warning text-dark">Tour {{ $currentTurn }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <form action="{{ route('game.orders.cancel', $order->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet ordre ?')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <p class="text-muted">Aucun ordre en attente.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Ordres traités -->
                        <div class="tab-pane fade" id="processed" role="tabpanel">
                            @if($processedOrders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Détails</th>
                                                <th>Tour</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($processedOrders as $order)
                                                <tr>
                                                    <td>
                                                        @switch($order->order_type)
                                                            @case('move')
                                                                <span class="badge bg-info"><i class="fas fa-route"></i> Déplacement</span>
                                                                @break
                                                            @case('colonize')
                                                                <span class="badge bg-success"><i class="fas fa-flag"></i> Colonisation</span>
                                                                @break
                                                            @case('research')
                                                                <span class="badge bg-primary"><i class="fas fa-flask"></i> Recherche</span>
                                                                @break
                                                            @case('build')
                                                                <span class="badge bg-warning text-dark"><i class="fas fa-hammer"></i> Construction</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary"><i class="fas fa-cog"></i> {{ ucfirst($order->order_type) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-link order-details-btn" data-order-id="{{ $order->id }}">
                                                            {{ $order->getShortDescription() }}
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Tour {{ $order->turn_execution }}</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-info order-details-btn" data-order-id="{{ $order->id }}">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <p class="text-muted">Aucun ordre traité.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Ordres échoués -->
                        <div class="tab-pane fade" id="failed" role="tabpanel">
                            @if($failedOrders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Détails</th>
                                                <th>Tour</th>
                                                <th>Erreur</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($failedOrders as $order)
                                                <tr>
                                                    <td>
                                                        @switch($order->order_type)
                                                            @case('move')
                                                                <span class="badge bg-info"><i class="fas fa-route"></i> Déplacement</span>
                                                                @break
                                                            @case('colonize')
                                                                <span class="badge bg-success"><i class="fas fa-flag"></i> Colonisation</span>
                                                                @break
                                                            @case('research')
                                                                <span class="badge bg-primary"><i class="fas fa-flask"></i> Recherche</span>
                                                                @break
                                                            @case('build')
                                                                <span class="badge bg-warning text-dark"><i class="fas fa-hammer"></i> Construction</span>
                                                                @break
                                                            @default
                                                                <span class="badge bg-secondary"><i class="fas fa-cog"></i> {{ ucfirst($order->order_type) }}</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-link order-details-btn" data-order-id="{{ $order->id }}">
                                                            {{ $order->getShortDescription() }}
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-danger">Tour {{ $order->turn_execution }}</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ $order->processing_error }}">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <p class="text-muted">Aucun ordre échoué.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Colonne de droite : Rapports -->
        <div class="col-md-5 mb-4">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header bg-secondary text-white">
                    <h4><i class="fas fa-file-alt"></i> Rapports</h4>
                </div>
                
                <div class="card-body p-0">
                    <ul class="nav nav-tabs" id="reportsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="turn-reports-tab" data-bs-toggle="tab" data-bs-target="#turn-reports" type="button" role="tab">
                                Tours <span class="badge bg-primary">{{ $turnReports->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="combat-reports-tab" data-bs-toggle="tab" data-bs-target="#combat-reports" type="button" role="tab">
                                Combats <span class="badge bg-danger">{{ $combatReports->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab">
                                Événements <span class="badge bg-info">{{ $events->count() }}</span>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-3" id="reportsTabContent">
                        <!-- Rapports de tour -->
                        <div class="tab-pane fade show active" id="turn-reports" role="tabpanel">
                            @if($turnReports->count() > 0)
                                <div class="list-group reports-list">
                                    @foreach($turnReports as $report)
                                        <a href="{{ route('game.reports.turns.show', $report->id) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary {{ $report->is_read ? '' : 'unread' }}">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    @if(!$report->is_read)
                                                        <span class="badge bg-warning text-dark">Nouveau</span>
                                                    @endif
                                                    Rapport du Tour {{ $report->turn_number }}
                                                </h6>
                                                <small>{{ $report->created_at->format('d/m/Y') }}</small>
                                            </div>
                                            <p class="mb-1 small">{{ Str::limit($report->summary, 100) }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <p class="text-muted">Aucun rapport de tour disponible.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Rapports de combat -->
                        <div class="tab-pane fade" id="combat-reports" role="tabpanel">
                            @if($combatReports->count() > 0)
                                <div class="list-group reports-list">
                                    @foreach($combatReports as $report)
                                        <a href="{{ route('game.reports.combats.show', $report->id) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    Combat dans {{ $report->location }}
                                                </h6>
                                                <small>Tour {{ $report->gameEvent->turn_number }}</small>
                                            </div>
                                            <p class="mb-1 small">
                                                @if($report->victory)
                                                    <span class="text-success"><i class="fas fa-trophy"></i> Victoire</span>
                                                @elseif($report->defeat)
                                                    <span class="text-danger"><i class="fas fa-skull-crossbones"></i> Défaite</span>
                                                @else
                                                    <span class="text-warning"><i class="fas fa-balance-scale"></i> Match nul</span>
                                                @endif
                                                - {{ $report->getSummary() }}
                                            </p>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <p class="text-muted">Aucun rapport de combat disponible.</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Événements -->
                        <div class="tab-pane fade" id="events" role="tabpanel">
                            @if($events->count() > 0)
                                <div class="list-group reports-list">
                                    @foreach($events as $event)
                                        <a href="{{ route('game.reports.events.show', $event->id) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    @switch($event->event_type)
                                                        @case('discovery')
                                                            <i class="fas fa-search"></i> Découverte
                                                            @break
                                                        @case('diplomatic')
                                                            <i class="fas fa-handshake"></i> Diplomatie
                                                            @break
                                                        @case('disaster')
                                                            <i class="fas fa-meteor"></i> Catastrophe
                                                            @break
                                                        @case('special')
                                                            <i class="fas fa-star"></i> Événement spécial
                                                            @break
                                                        @default
                                                            <i class="fas fa-bell"></i> {{ ucfirst($event->event_type) }}
                                                    @endswitch
                                                </h6>
                                                <small>Tour {{ $event->turn_number }}</small>
                                            </div>
                                            <p class="mb-1 small">{{ $event->getSummary() }}</p>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center p-4">
                                    <p class="text-muted">Aucun événement disponible.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de détails d'ordre -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="orderDetailsTitle">Détails de l'ordre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Le contenu sera chargé dynamiquement par JavaScript -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-danger" id="cancelOrderButton" style="display: none;">
                    <i class="fas fa-times"></i> Annuler l'ordre
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de nouvel ordre -->
<div class="modal fade" id="newOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title">Nouvel ordre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sélectionnez le type d'ordre que vous souhaitez donner :</p>
                
                <div class="list-group">
                    <a href="{{ route('game.orders.create', ['type' => 'move']) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-route"></i> Déplacement</h6>
                        </div>
                        <p class="mb-1 small">Déplacer une flotte vers un autre système stellaire.</p>
                    </a>
                    
                    <a href="{{ route('game.orders.create', ['type' => 'colonize']) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-flag"></i> Colonisation</h6>
                        </div>
                        <p class="mb-1 small">Coloniser une planète avec un vaisseau colonisateur.</p>
                    </a>
                    
                    <a href="{{ route('game.orders.create', ['type' => 'build']) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-hammer"></i> Construction</h6>
                        </div>
                        <p class="mb-1 small">Construire un bâtiment ou un vaisseau.</p>
                    </a>
                    
                    <a href="{{ route('game.orders.create', ['type' => 'research']) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-flask"></i> Recherche</h6>
                        </div>
                        <p class="mb-1 small">Rechercher une nouvelle technologie.</p>
                    </a>
                    
                    <a href="{{ route('game.orders.create', ['type' => 'diplomatic']) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><i class="fas fa-handshake"></i> Diplomatie</h6>
                        </div>
                        <p class="mb-1 small">Envoyer une proposition diplomatique à un autre commandant.</p>
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Élément caché contenant les données JSON pour le JavaScript -->
<script type="application/json" id="orders-data">
    {!! json_encode([
        'orders' => [
            'pending' => $pendingOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'type' => $order->order_type,
                    'description' => $order->getShortDescription(),
                    'turn_submitted' => $order->turn_submitted,
                    'turn_execution' => $order->turn_execution
                ];
            }),
            'processed' => $processedOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'type' => $order->order_type,
                    'description' => $order->getShortDescription(),
                    'turn_submitted' => $order->turn_submitted,
                    'turn_execution' => $order->turn_execution
                ];
            }),
            'failed' => $failedOrders->map(function($order) {
                return [
                    'id' => $order->id,
                    'type' => $order->order_type,
                    'description' => $order->getShortDescription(),
                    'turn_submitted' => $order->turn_submitted,
                    'turn_execution' => $order->turn_execution,
                    'error' => $order->processing_error
                ];
            })
        ],
        'current_turn' => $currentTurn
    ]) !!}
</script>

@endsection

@push('scripts')
<script src="{{ asset('js/orders.js') }}"></script>
@endpush
