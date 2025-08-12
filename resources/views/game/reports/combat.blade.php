@extends('layouts.app')

@section('title', 'Rapport de Combat - ' . $report->location)

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
                    <li class="breadcrumb-item"><a href="{{ route('game.reports.combats.index') }}">Rapports de Combat</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $report->location }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="fas fa-fighter-jet"></i> Rapport de Combat - {{ $report->location }}
                        @if($report->victory)
                            <span class="badge bg-success ms-2"><i class="fas fa-trophy"></i> Victoire</span>
                        @elseif($report->defeat)
                            <span class="badge bg-danger ms-2"><i class="fas fa-skull-crossbones"></i> Défaite</span>
                        @else
                            <span class="badge bg-warning ms-2"><i class="fas fa-balance-scale"></i> Match nul</span>
                        @endif
                    </h4>
                    <div>
                        <a href="{{ route('game.reports.combats.download', $report->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-download"></i> Télécharger
                        </a>
                        <a href="{{ route('game.reports.combats.index') }}" class="btn btn-sm btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Retour à la liste
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="report-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h5>Résumé</h5>
                                <p class="report-summary">{{ $report->getSummary() }}</p>
                            </div>
                            <div class="col-md-4">
                                <div class="text-end">
                                    <p class="mb-1"><strong>Tour:</strong> {{ $report->gameEvent->turn_number }}</p>
                                    <p class="mb-1"><strong>Date:</strong> {{ $report->created_at->format('d/m/Y H:i') }}</p>
                                    <p><strong>Lieu:</strong> {{ $report->location }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Participants -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-users"></i> Participants</h5>
                        
                        <div class="row">
                            <!-- Forces alliées -->
                            <div class="col-md-6 mb-3">
                                <div class="card bg-dark border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">Forces alliées</h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach($report->participants['allies'] as $ally)
                                            <div class="mb-3">
                                                <h6>{{ $ally['commander_name'] }}</h6>
                                                
                                                @foreach($ally['fleets'] as $fleet)
                                                    <div class="card bg-dark border-secondary mb-2">
                                                        <div class="card-header bg-dark py-2 d-flex justify-content-between align-items-center">
                                                            <span>{{ $fleet['name'] }}</span>
                                                            <span class="badge bg-info">{{ count($fleet['ships']) }} vaisseaux</span>
                                                        </div>
                                                        <div class="card-body p-2">
                                                            <div class="table-responsive">
                                                                <table class="table table-dark table-sm mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Vaisseau</th>
                                                                            <th>État initial</th>
                                                                            <th>État final</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($fleet['ships'] as $ship)
                                                                            <tr>
                                                                                <td>{{ $ship['name'] }} ({{ $ship['class'] }})</td>
                                                                                <td>
                                                                                    <div class="progress" style="height: 5px;">
                                                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $ship['initial_hull_percent'] }}%"></div>
                                                                                    </div>
                                                                                    <div class="progress mt-1" style="height: 5px;">
                                                                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $ship['initial_shield_percent'] }}%"></div>
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    @if($ship['destroyed'])
                                                                                        <span class="badge bg-danger">Détruit</span>
                                                                                    @else
                                                                                        <div class="progress" style="height: 5px;">
                                                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $ship['final_hull_percent'] }}%"></div>
                                                                                        </div>
                                                                                        <div class="progress mt-1" style="height: 5px;">
                                                                                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $ship['final_shield_percent'] }}%"></div>
                                                                                        </div>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Forces ennemies -->
                            <div class="col-md-6 mb-3">
                                <div class="card bg-dark border-danger">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">Forces ennemies</h6>
                                    </div>
                                    <div class="card-body">
                                        @foreach($report->participants['enemies'] as $enemy)
                                            <div class="mb-3">
                                                <h6>{{ $enemy['commander_name'] }}</h6>
                                                
                                                @foreach($enemy['fleets'] as $fleet)
                                                    <div class="card bg-dark border-secondary mb-2">
                                                        <div class="card-header bg-dark py-2 d-flex justify-content-between align-items-center">
                                                            <span>{{ $fleet['name'] }}</span>
                                                            <span class="badge bg-info">{{ count($fleet['ships']) }} vaisseaux</span>
                                                        </div>
                                                        <div class="card-body p-2">
                                                            <div class="table-responsive">
                                                                <table class="table table-dark table-sm mb-0">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Vaisseau</th>
                                                                            <th>État initial</th>
                                                                            <th>État final</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($fleet['ships'] as $ship)
                                                                            <tr>
                                                                                <td>{{ $ship['name'] }} ({{ $ship['class'] }})</td>
                                                                                <td>
                                                                                    <div class="progress" style="height: 5px;">
                                                                                        <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $ship['initial_hull_percent'] }}%"></div>
                                                                                    </div>
                                                                                    <div class="progress mt-1" style="height: 5px;">
                                                                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $ship['initial_shield_percent'] }}%"></div>
                                                                                    </div>
                                                                                </td>
                                                                                <td>
                                                                                    @if($ship['destroyed'])
                                                                                        <span class="badge bg-danger">Détruit</span>
                                                                                    @else
                                                                                        <div class="progress" style="height: 5px;">
                                                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $ship['final_hull_percent'] }}%"></div>
                                                                                        </div>
                                                                                        <div class="progress mt-1" style="height: 5px;">
                                                                                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $ship['final_shield_percent'] }}%"></div>
                                                                                        </div>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Déroulement du combat -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-history"></i> Déroulement du combat</h5>
                        
                        <div class="combat-timeline">
                            @foreach($report->combat_rounds as $roundIndex => $round)
                                <div class="combat-round">
                                    <div class="round-header" data-bs-toggle="collapse" data-bs-target="#round{{ $roundIndex }}" aria-expanded="{{ $roundIndex === 0 ? 'true' : 'false' }}">
                                        <h6>Round {{ $roundIndex + 1 }}</h6>
                                        <span class="round-toggle"><i class="fas fa-chevron-down"></i></span>
                                    </div>
                                    
                                    <div class="collapse {{ $roundIndex === 0 ? 'show' : '' }}" id="round{{ $roundIndex }}">
                                        <div class="round-content">
                                            @foreach($round['actions'] as $action)
                                                <div class="combat-action {{ $action['side'] === 'ally' ? 'ally-action' : 'enemy-action' }}">
                                                    <div class="action-time">{{ $action['time'] }}</div>
                                                    <div class="action-content">
                                                        <div class="action-header">
                                                            @if($action['side'] === 'ally')
                                                                <span class="badge bg-primary">Allié</span>
                                                            @else
                                                                <span class="badge bg-danger">Ennemi</span>
                                                            @endif
                                                            <strong>{{ $action['ship_name'] }}</strong>
                                                        </div>
                                                        <div class="action-description">
                                                            @switch($action['type'])
                                                                @case('attack')
                                                                    <i class="fas fa-crosshairs text-danger"></i> 
                                                                    Attaque {{ $action['target_name'] }} 
                                                                    @if($action['hit'])
                                                                        et inflige <strong>{{ $action['damage'] }}</strong> points de dégâts
                                                                        @if($action['critical'])
                                                                            <span class="text-warning">(Coup critique!)</span>
                                                                        @endif
                                                                        @if($action['shield_hit'])
                                                                            aux boucliers
                                                                        @else
                                                                            à la coque
                                                                        @endif
                                                                    @else
                                                                        mais <span class="text-muted">rate sa cible</span>
                                                                    @endif
                                                                    @break
                                                                    
                                                                @case('shield_regen')
                                                                    <i class="fas fa-shield-alt text-info"></i>
                                                                    Régénère <strong>{{ $action['amount'] }}</strong> points de bouclier
                                                                    @break
                                                                    
                                                                @case('repair')
                                                                    <i class="fas fa-wrench text-success"></i>
                                                                    Répare <strong>{{ $action['amount'] }}</strong> points de coque
                                                                    @break
                                                                    
                                                                @case('special')
                                                                    <i class="fas fa-star text-warning"></i>
                                                                    {{ $action['description'] }}
                                                                    @break
                                                                    
                                                                @case('destroyed')
                                                                    <i class="fas fa-skull-crossbones text-danger"></i>
                                                                    <strong>Vaisseau détruit!</strong>
                                                                    @break
                                                                    
                                                                @default
                                                                    {{ $action['description'] }}
                                                            @endswitch
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            <div class="round-summary">
                                                <h6>Résumé du round {{ $roundIndex + 1 }}</h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <p><strong>Forces alliées:</strong></p>
                                                        <ul>
                                                            <li>Dégâts infligés: {{ number_format($round['summary']['ally_damage_dealt']) }}</li>
                                                            <li>Dégâts subis: {{ number_format($round['summary']['ally_damage_taken']) }}</li>
                                                            <li>Vaisseaux perdus: {{ $round['summary']['ally_ships_lost'] }}</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Forces ennemies:</strong></p>
                                                        <ul>
                                                            <li>Dégâts infligés: {{ number_format($round['summary']['enemy_damage_dealt']) }}</li>
                                                            <li>Dégâts subis: {{ number_format($round['summary']['enemy_damage_taken']) }}</li>
                                                            <li>Vaisseaux perdus: {{ $round['summary']['enemy_ships_lost'] }}</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Résultats -->
                    <div class="report-section">
                        <h5><i class="fas fa-chart-pie"></i> Résultats</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-dark border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Statistiques du combat</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <p><strong>Durée:</strong> {{ count($report->combat_rounds) }} rounds</p>
                                                <p><strong>Vaisseaux alliés détruits:</strong> {{ $report->results['ally_ships_destroyed'] }}</p>
                                                <p><strong>Vaisseaux ennemis détruits:</strong> {{ $report->results['enemy_ships_destroyed'] }}</p>
                                            </div>
                                            <div class="col-6">
                                                <p><strong>Dégâts infligés:</strong> {{ number_format($report->results['total_damage_dealt']) }}</p>
                                                <p><strong>Dégâts subis:</strong> {{ number_format($report->results['total_damage_taken']) }}</p>
                                                <p><strong>Ratio d'efficacité:</strong> {{ $report->results['efficiency_ratio'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card bg-dark border-secondary">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">Récompenses et pertes</h6>
                                    </div>
                                    <div class="card-body">
                                        @if($report->victory)
                                            <div class="alert alert-success">
                                                <i class="fas fa-trophy"></i> <strong>Victoire!</strong> Vos forces ont triomphé.
                                            </div>
                                            
                                            @if(!empty($report->results['rewards']))
                                                <h6>Récompenses</h6>
                                                <ul>
                                                    @foreach($report->results['rewards'] as $reward)
                                                        <li>{{ $reward }}</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @elseif($report->defeat)
                                            <div class="alert alert-danger">
                                                <i class="fas fa-skull-crossbones"></i> <strong>Défaite!</strong> Vos forces ont été vaincues.
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fas fa-balance-scale"></i> <strong>Match nul!</strong> Les deux camps se sont retirés.
                                            </div>
                                        @endif
                                        
                                        <h6>Pertes</h6>
                                        <ul>
                                            @foreach($report->results['losses'] as $loss)
                                                <li>{{ $loss }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
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
<script type="application/json" id="combat-report-data">
    {!! json_encode([
        'report' => [
            'id' => $report->id,
            'location' => $report->location,
            'participants' => $report->participants,
            'combat_rounds' => $report->combat_rounds,
            'results' => $report->results,
            'victory' => $report->victory,
            'defeat' => $report->defeat
        ]
    ]) !!}
</script>

@endsection

@push('scripts')
<script src="{{ asset('js/combat-reports.js') }}"></script>
@endpush
