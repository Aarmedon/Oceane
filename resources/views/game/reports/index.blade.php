@extends('layouts.app')

@section('title', 'Centre des Rapports')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
<style>
    .report-type-card {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .report-type-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
    }
    
    .report-type-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    
    .report-count {
        position: absolute;
        top: 15px;
        right: 15px;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('game.orders.index') }}">Centre de Commandement</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Centre des Rapports</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h4><i class="fas fa-file-alt"></i> Centre des Rapports</h4>
                </div>
                
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Bienvenue dans le centre des rapports. Consultez ici tous les rapports et événements concernant votre empire.
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <!-- Rapports de tour -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-dark border-primary h-100 report-type-card" onclick="window.location.href='{{ route('game.reports.turns.index') }}'">
                                <div class="card-body text-center">
                                    <span class="badge bg-primary report-count">{{ $turnReportsCount ?? 0 }}</span>
                                    <div class="report-type-icon text-primary">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h5 class="card-title">Rapports de Tour</h5>
                                    <p class="card-text">Consultez les résumés détaillés de chaque tour de jeu, incluant les activités planétaires, les mouvements de flottes et les bilans financiers.</p>
                                </div>
                                <div class="card-footer bg-dark border-top border-primary">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Dernier rapport: {{ $lastTurnReport ? $lastTurnReport->created_at->format('d/m/Y') : 'Aucun' }}</small>
                                        <span class="badge {{ ($unreadTurnReports ?? 0) > 0 ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $unreadTurnReports ?? 0 }} non lu(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Rapports de combat -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-dark border-danger h-100 report-type-card" onclick="window.location.href='{{ route('game.reports.combats.index') }}'">
                                <div class="card-body text-center">
                                    <span class="badge bg-danger report-count">{{ $combatReportsCount ?? 0 }}</span>
                                    <div class="report-type-icon text-danger">
                                        <i class="fas fa-fighter-jet"></i>
                                    </div>
                                    <h5 class="card-title">Rapports de Combat</h5>
                                    <p class="card-text">Analysez les affrontements spatiaux, les tactiques utilisées et les résultats des batailles impliquant vos flottes.</p>
                                </div>
                                <div class="card-footer bg-dark border-top border-danger">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Dernier combat: {{ $lastCombatReport ? $lastCombatReport->created_at->format('d/m/Y') : 'Aucun' }}</small>
                                        <span class="badge {{ ($unreadCombatReports ?? 0) > 0 ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $unreadCombatReports ?? 0 }} non lu(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Événements -->
                        <div class="col-md-4 mb-4">
                            <div class="card bg-dark border-warning h-100 report-type-card" onclick="window.location.href='{{ route('game.reports.events.index') }}'">
                                <div class="card-body text-center">
                                    <span class="badge bg-warning text-dark report-count">{{ $eventsCount ?? 0 }}</span>
                                    <div class="report-type-icon text-warning">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <h5 class="card-title">Événements</h5>
                                    <p class="card-text">Suivez les événements importants qui affectent votre empire, des découvertes scientifiques aux incidents diplomatiques.</p>
                                </div>
                                <div class="card-footer bg-dark border-top border-warning">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Dernier événement: {{ $lastEvent ? $lastEvent->created_at->format('d/m/Y') : 'Aucun' }}</small>
                                        <span class="badge {{ ($unreadEvents ?? 0) > 0 ? 'bg-info' : 'bg-secondary' }}">
                                            {{ $unreadEvents ?? 0 }} non lu(s)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistiques et graphiques -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-dark border-secondary">
                                <div class="card-header bg-dark">
                                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Aperçu des activités récentes</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <canvas id="reports-activity-chart" height="250"></canvas>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="border-bottom border-secondary pb-2 mb-3">Statistiques récentes</h6>
                                            <ul class="list-group list-group-flush bg-transparent">
                                                <li class="list-group-item bg-dark d-flex justify-content-between align-items-center">
                                                    <span>Rapports de tour</span>
                                                    <span class="badge bg-primary rounded-pill">{{ $turnReportsCount ?? 0 }}</span>
                                                </li>
                                                <li class="list-group-item bg-dark d-flex justify-content-between align-items-center">
                                                    <span>Combats</span>
                                                    <span class="badge bg-danger rounded-pill">{{ $combatReportsCount ?? 0 }}</span>
                                                </li>
                                                <li class="list-group-item bg-dark d-flex justify-content-between align-items-center">
                                                    <span>Événements</span>
                                                    <span class="badge bg-warning text-dark rounded-pill">{{ $eventsCount ?? 0 }}</span>
                                                </li>
                                                <li class="list-group-item bg-dark d-flex justify-content-between align-items-center">
                                                    <span>Événements importants</span>
                                                    <span class="badge bg-danger rounded-pill">{{ $importantEventsCount ?? 0 }}</span>
                                                </li>
                                                <li class="list-group-item bg-dark d-flex justify-content-between align-items-center">
                                                    <span>Non lus</span>
                                                    <span class="badge bg-info rounded-pill">{{ ($unreadTurnReports ?? 0) + ($unreadCombatReports ?? 0) + ($unreadEvents ?? 0) }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Derniers événements -->
                    @if(isset($recentEvents) && count($recentEvents) > 0)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-dark border-secondary">
                                <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-bell"></i> Événements récents</h5>
                                    <a href="{{ route('game.reports.events.index') }}" class="btn btn-sm btn-outline-info">Voir tous</a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        @foreach($recentEvents as $event)
                                        <a href="{{ route('game.reports.events.show', $event->id) }}" class="list-group-item list-group-item-action bg-dark border-secondary d-flex align-items-center">
                                            <div class="me-3">
                                                <div class="bg-{{ $event->getColorClass() }} rounded-circle p-2 text-center" style="width: 40px; height: 40px;">
                                                    <i class="fas {{ $event->getIconClass() }}"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1">{{ $event->title }}</h6>
                                                    <small>{{ $event->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="mb-1 text-truncate">{{ $event->description }}</p>
                                            </div>
                                            @if(!$event->read)
                                            <div class="ms-2">
                                                <span class="badge bg-info">Nouveau</span>
                                            </div>
                                            @endif
                                        </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="card-footer text-end">
                    <a href="{{ route('game.orders.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au Centre de Commandement
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Données pour les graphiques -->
<script type="application/json" id="reports-data">
    {!! json_encode([
        'activity' => [
            'labels' => $activityLabels ?? [],
            'turns' => $activityTurns ?? [],
            'combats' => $activityCombats ?? [],
            'events' => $activityEvents ?? []
        ]
    ]) !!}
</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('js/reports-index.js') }}"></script>
@endpush
