@extends('layouts.app')

@section('title', 'Rapport du Tour ' . $report->turn_number)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('game.reports.index') }}">Centre des Rapports</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('game.reports.turns.index') }}">Rapports de Tour</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tour {{ $report->turn_number }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-file-alt"></i> Rapport du Tour {{ $report->turn_number }}</h4>
                    <div>
                        <a href="{{ route('game.reports.turns.download', $report->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-download"></i> Télécharger
                        </a>
                        <a href="{{ route('game.reports.turns.index') }}" class="btn btn-sm btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="report-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Résumé</h5>
                                <p class="report-summary">{{ $report->summary }}</p>
                            </div>
                            <div class="col-md-4">
                                <div class="text-end">
                                    <p class="mb-1"><strong>Date:</strong> {{ $report->created_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>Commandant:</strong> {{ $commander->name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Rapport financier -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-coins"></i> Rapport Financier</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-dark border-secondary mb-3">
                                    <div class="card-header bg-secondary text-white">Revenus</div>
                                    <div class="card-body">
                                        <table class="table table-dark table-sm">
                                            <tbody>
                                                @foreach($report->financial_report['income'] as $source => $amount)
                                                <tr>
                                                    <td>{{ ucfirst($source) }}</td>
                                                    <td class="text-end">{{ number_format($amount) }} <i class="fas fa-coins text-warning"></i></td>
                                                </tr>
                                                @endforeach
                                                <tr class="table-secondary">
                                                    <td><strong>Total des revenus</strong></td>
                                                    <td class="text-end"><strong>{{ number_format($report->financial_report['total_income']) }} <i class="fas fa-coins text-warning"></i></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-dark border-secondary mb-3">
                                    <div class="card-header bg-secondary text-white">Dépenses</div>
                                    <div class="card-body">
                                        <table class="table table-dark table-sm">
                                            <tbody>
                                                @foreach($report->financial_report['expenses'] as $source => $amount)
                                                <tr>
                                                    <td>{{ ucfirst($source) }}</td>
                                                    <td class="text-end">{{ number_format($amount) }} <i class="fas fa-coins text-warning"></i></td>
                                                </tr>
                                                @endforeach
                                                <tr class="table-secondary">
                                                    <td><strong>Total des dépenses</strong></td>
                                                    <td class="text-end"><strong>{{ number_format($report->financial_report['total_expenses']) }} <i class="fas fa-coins text-warning"></i></strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card bg-dark border-secondary">
                            <div class="card-header bg-secondary text-white">Bilan</div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Solde précédent:</strong> {{ number_format($report->financial_report['previous_balance']) }} <i class="fas fa-coins text-warning"></i></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Solde actuel:</strong> {{ number_format($report->financial_report['current_balance']) }} <i class="fas fa-coins text-warning"></i></p>
                                    </div>
                                </div>
                                <div class="progress">
                                    @php
                                        $balanceDiff = $report->financial_report['current_balance'] - $report->financial_report['previous_balance'];
                                        $balanceClass = $balanceDiff >= 0 ? 'bg-success' : 'bg-danger';
                                        $balancePercent = abs($balanceDiff) / max(1, $report->financial_report['previous_balance']) * 100;
                                        $balancePercent = min(100, $balancePercent);
                                    @endphp
                                    <div class="progress-bar {{ $balanceClass }}" role="progressbar" style="width: {{ $balancePercent }}%">
                                        {{ $balanceDiff >= 0 ? '+' : '-' }}{{ number_format(abs($balanceDiff)) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activités des planètes -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-globe"></i> Activités des Planètes</h5>
                        
                        @if(count($report->planets_activity) > 0)
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Planète</th>
                                            <th>Population</th>
                                            <th>Production</th>
                                            <th>Constructions</th>
                                            <th>Événements</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report->planets_activity as $planetActivity)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('game.planets.show', $planetActivity['planet_id']) }}">
                                                        {{ $planetActivity['name'] }}
                                                    </a>
                                                </td>
                                                <td>
                                                    {{ number_format($planetActivity['population']) }}
                                                    @if($planetActivity['population_change'] > 0)
                                                        <span class="text-success ms-1"><i class="fas fa-arrow-up"></i> {{ number_format($planetActivity['population_change']) }}</span>
                                                    @elseif($planetActivity['population_change'] < 0)
                                                        <span class="text-danger ms-1"><i class="fas fa-arrow-down"></i> {{ number_format(abs($planetActivity['population_change'])) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @foreach($planetActivity['production'] as $resource => $amount)
                                                        <div>{{ ucfirst($resource) }}: {{ number_format($amount) }}</div>
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @if(count($planetActivity['constructions']) > 0)
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($planetActivity['constructions'] as $construction)
                                                                <li>
                                                                    @if($construction['completed'])
                                                                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                                                                    @else
                                                                        <span class="text-warning"><i class="fas fa-hammer"></i></span>
                                                                    @endif
                                                                    {{ $construction['name'] }}
                                                                    @if(!$construction['completed'])
                                                                        ({{ $construction['progress'] }}%)
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">Aucune</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(count($planetActivity['events']) > 0)
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($planetActivity['events'] as $event)
                                                                <li>
                                                                    @switch($event['type'])
                                                                        @case('disaster')
                                                                            <span class="text-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                                                            @break
                                                                        @case('discovery')
                                                                            <span class="text-info"><i class="fas fa-search"></i></span>
                                                                            @break
                                                                        @default
                                                                            <span class="text-secondary"><i class="fas fa-info-circle"></i></span>
                                                                    @endswitch
                                                                    {{ $event['description'] }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">Aucun</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle"></i> Aucune activité planétaire à signaler pour ce tour.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Activités des flottes -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-space-shuttle"></i> Activités des Flottes</h5>
                        
                        @if(count($report->fleets_activity) > 0)
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Flotte</th>
                                            <th>Position</th>
                                            <th>Statut</th>
                                            <th>Activités</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report->fleets_activity as $fleetActivity)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('game.fleets.show', $fleetActivity['fleet_id']) }}">
                                                        {{ $fleetActivity['name'] }}
                                                    </a>
                                                </td>
                                                <td>
                                                    @if($fleetActivity['is_moving'])
                                                        En route vers {{ $fleetActivity['destination'] }}
                                                        <div class="small text-muted">
                                                            Arrivée dans {{ $fleetActivity['turns_remaining'] }} tour(s)
                                                        </div>
                                                    @else
                                                        {{ $fleetActivity['location'] }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @switch($fleetActivity['status'])
                                                        @case('idle')
                                                            <span class="badge bg-secondary">En attente</span>
                                                            @break
                                                        @case('moving')
                                                            <span class="badge bg-info">En mouvement</span>
                                                            @break
                                                        @case('combat')
                                                            <span class="badge bg-danger">Combat</span>
                                                            @break
                                                        @case('patrol')
                                                            <span class="badge bg-primary">Patrouille</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ ucfirst($fleetActivity['status']) }}</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    @if(count($fleetActivity['activities']) > 0)
                                                        <ul class="list-unstyled mb-0">
                                                            @foreach($fleetActivity['activities'] as $activity)
                                                                <li>
                                                                    @switch($activity['type'])
                                                                        @case('combat')
                                                                            <span class="text-danger"><i class="fas fa-fighter-jet"></i></span>
                                                                            @break
                                                                        @case('scan')
                                                                            <span class="text-info"><i class="fas fa-satellite-dish"></i></span>
                                                                            @break
                                                                        @case('colonize')
                                                                            <span class="text-success"><i class="fas fa-flag"></i></span>
                                                                            @break
                                                                        @default
                                                                            <span class="text-secondary"><i class="fas fa-cog"></i></span>
                                                                    @endswitch
                                                                    {{ $activity['description'] }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">Aucune</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle"></i> Aucune activité de flotte à signaler pour ce tour.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Recherches -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-flask"></i> Recherches</h5>
                        
                        @if($report->research_activity)
                            <div class="card bg-dark border-secondary">
                                <div class="card-body">
                                    @if($report->research_activity['completed'])
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> <strong>Recherche terminée !</strong> 
                                            La technologie <strong>{{ $report->research_activity['technology_name'] }}</strong> a été découverte au niveau {{ $report->research_activity['level'] }}.
                                        </div>
                                        <p>{{ $report->research_activity['benefits'] }}</p>
                                    @else
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 me-3">
                                                <h6>{{ $report->research_activity['technology_name'] }}</h6>
                                                <div class="progress">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $report->research_activity['progress'] }}%" aria-valuenow="{{ $report->research_activity['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                                                        {{ $report->research_activity['progress'] }}%
                                                    </div>
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    Points restants : {{ number_format($report->research_activity['points_remaining']) }} / {{ number_format($report->research_activity['total_points']) }}
                                                </div>
                                            </div>
                                            <div>
                                                <span class="badge bg-info">{{ $report->research_activity['turns_remaining'] }} tour(s) restant(s)</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle"></i> Aucune recherche en cours pour ce tour.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Diplomatie -->
                    <div class="report-section">
                        <h5><i class="fas fa-handshake"></i> Diplomatie</h5>
                        
                        @if(count($report->diplomatic_activity) > 0)
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Commandant</th>
                                            <th>Type</th>
                                            <th>Message</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($report->diplomatic_activity as $diplomacy)
                                            <tr>
                                                <td>{{ $diplomacy['commander_name'] }}</td>
                                                <td>
                                                    @switch($diplomacy['type'])
                                                        @case('alliance_proposal')
                                                            <span class="badge bg-success">Alliance</span>
                                                            @break
                                                        @case('peace_proposal')
                                                            <span class="badge bg-info">Paix</span>
                                                            @break
                                                        @case('trade_proposal')
                                                            <span class="badge bg-warning text-dark">Commerce</span>
                                                            @break
                                                        @case('war_declaration')
                                                            <span class="badge bg-danger">Guerre</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ ucfirst($diplomacy['type']) }}</span>
                                                    @endswitch
                                                </td>
                                                <td>{{ $diplomacy['message'] }}</td>
                                                <td>
                                                    @switch($diplomacy['status'])
                                                        @case('pending')
                                                            <span class="badge bg-warning text-dark">En attente</span>
                                                            @break
                                                        @case('accepted')
                                                            <span class="badge bg-success">Accepté</span>
                                                            @break
                                                        @case('rejected')
                                                            <span class="badge bg-danger">Rejeté</span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ ucfirst($diplomacy['status']) }}</span>
                                                    @endswitch
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle"></i> Aucune activité diplomatique à signaler pour ce tour.
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="card-footer text-end">
                    <a href="{{ route('game.reports.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour au Centre des Rapports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Élément caché contenant les données JSON pour le JavaScript -->
<script type="application/json" id="report-data">
    {!! json_encode([
        'report' => [
            'id' => $report->id,
            'turn_number' => $report->turn_number,
            'financial_report' => $report->financial_report,
            'planets_activity' => $report->planets_activity,
            'fleets_activity' => $report->fleets_activity,
            'research_activity' => $report->research_activity,
            'diplomatic_activity' => $report->diplomatic_activity
        ]
    ]) !!}
</script>

@endsection

@push('scripts')
<script src="{{ asset('js/turn-reports.js') }}"></script>
@endpush
