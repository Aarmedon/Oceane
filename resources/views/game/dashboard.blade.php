@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h4><i class="fas fa-user-circle"></i> Commandant {{ $commander->name }}</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="{{ asset('images/races/' . $commander->race_id . '.png') }}" alt="{{ $commander->race->name }}" class="img-fluid mb-2" style="max-height: 150px;">
                            <p class="text-muted">{{ $commander->race->name }}</p>
                        </div>
                        <div class="col-md-5">
                            <h5>Information générale</h5>
                            <ul class="list-unstyled">
                                <li><strong><i class="fas fa-calendar-check"></i> Date de création:</strong> Tour {{ $commander->created_turn }}</li>
                                <li><strong><i class="fas fa-coins"></i> Crédits:</strong> {{ number_format($commander->credits, 0) }} cr</li>
                                <li><strong><i class="fas fa-star"></i> Réputation:</strong> {{ $commander->reputation }}</li>
                                <li><strong><i class="fas fa-globe"></i> Capitale:</strong> 
                                    @if($commander->capitalSystem)
                                        <a href="{{ route('game.star_system', $commander->capital_system_id) }}">{{ $commander->capitalSystem->name }}</a>
                                    @else
                                        Non définie
                                    @endif
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h5>Statistiques</h5>
                            <ul class="list-unstyled">
                                <li><strong><i class="fas fa-globe"></i> Systèmes:</strong> {{ $systemsCount }}</li>
                                <li><strong><i class="fas fa-planet"></i> Planètes:</strong> {{ $planetsCount }}</li>
                                <li><strong><i class="fas fa-rocket"></i> Flottes:</strong> {{ $fleetsCount }}</li>
                                <li>
                                    <strong><i class="fas fa-file-alt"></i> Rapports:</strong> 
                                    <a href="{{ route('game.reports.index') }}">
                                        {{ $unreadReportsCount }} non lus
                                        @if($unreadReportsCount > 0)
                                            <span class="badge bg-danger">{{ $unreadReportsCount }}</span>
                                        @endif
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @if($commander->description)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Biographie</h5>
                                <div class="p-3 border border-secondary rounded">
                                    {{ $commander->description }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne de gauche -->
        <div class="col-md-8">
            <!-- Derniers rapports -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas a-newspaper"></i> Derniers rapports</h5>
                    <a href="{{ route('game.reports.index') }}" class="btn btn-dark btn-sm">Voir tous</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($turnReports as $report)
                            <a href="{{ route('game.reports.turns.show', $report->id) }}" class="list-group-item list-group-item-action bg-dark text-light border-secondary d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Tour {{ $report->turn_number }}</h6>
                                    <p class="mb-1 text-truncate" style="max-width: 500px;">{{ Str::limit($report->summary, 100) }}</p>
                                </div>
                                @if(!$report->is_read)
                                    <span class="badge bg-danger">Non lu</span>
                                @endif
                            </a>
                        @empty
                            <div class="list-group-item bg-dark text-light border-secondary text-center">
                                Aucun rapport disponible
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Carte -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map"></i> Carte stellaire</h5>
                    <a href="{{ route('game.galaxy_map') }}" class="btn btn-dark btn-sm">Voir carte complète</a>
                </div>
                <div class="card-body">
                    @include('game.partials.galaxy_map_widget')
                    <div class="mt-3 d-flex justify-content-center">
                        <a href="{{ route('game.galaxy_map') }}" class="btn btn-primary me-2">
                            <i class="fas fa-globe"></i> Explorer la galaxie
                        </a>
                        @if($commander->capitalSystem)
                            <a href="{{ route('game.star_system', $commander->capital_system_id) }}" class="btn btn-info">
                                <i class="fas fa-home"></i> Voir ma capitale
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne de droite -->
        <div class="col-md-4">
            <!-- Actions rapides -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Actions rapides</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('game.fleets') }}" class="btn btn-primary">
                            <i class="fas fa-rocket"></i> Gérer mes flottes
                        </a>
                        <a href="{{ route('game.technologies') }}" class="btn btn-info">
                            <i class="fas fa-microchip"></i> Rechercher des technologies
                        </a>
                        <a href="{{ route('game.orders.index') }}" class="btn btn-warning">
                            <i class="fas fa-tasks"></i> Gérer mes ordres
                        </a>
                        @if($commander->alliance)
                            <a href="{{ route('game.alliance', $commander->alliance_id) }}" class="btn btn-success">
                                <i class="fas fa-users"></i> Mon alliance
                            </a>
                        @else
                            <a href="{{ route('game.alliances') }}" class="btn btn-outline-success">
                                <i class="fas fa-users"></i> Rejoindre une alliance
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Ordres en attente -->
            <div class="card bg-dark border-secondary mb-4">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Ordres en attente</h5>
                    <a href="{{ route('game.orders.index') }}" class="btn btn-dark btn-sm">Tous les ordres</a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse($latestOrders as $order)
                            <div class="list-group-item bg-dark text-light border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            @switch($order->order_type)
                                                @case('move_fleet')
                                                    <i class="fas fa-arrow-right"></i> Déplacement de flotte
                                                    @break
                                                @case('research_technology')
                                                    <i class="fas fa-flask"></i> Recherche
                                                    @break
                                                @case('build_ship')
                                                    <i class="fas fa-space-shuttle"></i> Construction de vaisseau
                                                    @break
                                                @default
                                                    <i class="fas fa-cog"></i> {{ ucfirst($order->order_type) }}
                                            @endswitch
                                        </h6>
                                        <p class="mb-0 small">Exécution au tour {{ $order->turn_execution }}</p>
                                    </div>
                                    <form action="{{ route('game.orders.cancel', $order->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet ordre?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="list-group-item bg-dark text-light border-secondary text-center">
                                Aucun ordre en attente
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            
            <!-- Tour actuel -->
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Information de jeu</h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h3>Tour actuel: {{ $currentTurn }}</h3>
                        <p>Prochain tour dans <span class="text-warning">12:34:56</span></p>
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 45%"></div>
                        </div>
                        <p class="small text-muted">Les ordres pour le tour {{ $currentTurn + 1 }} sont acceptés.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
