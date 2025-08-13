@extends('layouts.app')

@section('title', 'Événements')

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
                    <li class="breadcrumb-item"><a href="{{ route('game.orders.index') }}">Centre de Commandement</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Événements</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-bell"></i> Événements</h4>
                    <div>
                        <button class="btn btn-sm btn-info" id="mark-all-read" data-url="{{ route('game.reports.events.mark-all-read') }}">
                            <i class="fas fa-check-double"></i> Tout marquer comme lu
                        </button>
                        <a href="{{ route('game.reports.index') }}" class="btn btn-sm btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Retour au Centre des Rapports
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap">
                                <div class="me-2 mb-2">
                                    <button class="btn btn-sm btn-dark border-secondary filter-btn active" data-filter="all">
                                        Tous <span class="badge bg-secondary ms-1">{{ count($events) }}</span>
                                    </button>
                                </div>
                                <div class="me-2 mb-2">
                                    <button class="btn btn-sm btn-dark border-secondary filter-btn" data-filter="unread">
                                        Non lus <span class="badge bg-info ms-1">{{ $events->where('read', false)->count() }}</span>
                                    </button>
                                </div>
                                <div class="me-2 mb-2">
                                    <button class="btn btn-sm btn-dark border-secondary filter-btn" data-filter="important">
                                        Importants <span class="badge bg-danger ms-1">{{ $events->where('importance', '>=', 4)->count() }}</span>
                                    </button>
                                </div>
                                <div class="dropdown me-2 mb-2">
                                    <button class="btn btn-sm btn-dark border-secondary dropdown-toggle" type="button" id="typeFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Type
                                    </button>
                                    <ul class="dropdown-menu bg-dark" aria-labelledby="typeFilterDropdown">
                                        @php
                                            $eventTypes = $events->pluck('type')->unique();
                                        @endphp
                                        @foreach($eventTypes as $type)
                                            <li>
                                                <button class="dropdown-item text-light filter-btn" data-filter="type-{{ $type }}">
                                                    {{ \App\Models\GameEvent::getTypeText($type) }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <input type="text" class="form-control bg-dark text-light border-secondary" placeholder="Rechercher..." id="event-search">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liste des événements -->
                    <div class="row" id="events-container">
                        @forelse($events as $event)
                            <div class="col-md-6 mb-3 event-item" 
                                 data-importance="{{ $event->importance }}"
                                 data-type="{{ $event->type }}"
                                 data-read="{{ $event->read ? 'true' : 'false' }}"
                                 data-turn="{{ $event->turn_number }}"
                                 data-date="{{ $event->created_at->format('Y-m-d') }}">
                                <div class="card bg-dark border-secondary event-card event-importance-{{ $event->importance }}" 
                                     onclick="window.location.href='{{ route('game.reports.events.show', $event->id) }}'">
                                    @if(!$event->read)
                                        <div class="event-unread"></div>
                                    @endif
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="event-icon me-3 {{ $event->getBadgeClass() }}">
                                                <i class="fas {{ $event->getIconClass() }}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h5 class="mb-1">{{ $event->title }}</h5>
                                                    <span class="badge {{ $event->getBadgeClass() }}">{{ $event->getImportanceText() }}</span>
                                                </div>
                                                <p class="text-muted mb-2">Tour {{ $event->turn_number }} - {{ $event->created_at->format('d/m/Y') }}</p>
                                                <p class="mb-0">{{ $event->getShortDescription() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-dark border-secondary">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">{{ $event->getTypeText() }}</small>
                                            <div>
                                                @if($event->planet_id)
                                                    <span class="badge bg-info" data-bs-toggle="tooltip" data-bs-placement="top" title="Planète associée">
                                                        <i class="fas fa-globe"></i>
                                                    </span>
                                                @endif
                                                @if($event->fleet_id)
                                                    <span class="badge bg-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Flotte associée">
                                                        <i class="fas fa-space-shuttle"></i>
                                                    </span>
                                                @endif
                                                @if($event->technology_id)
                                                    <span class="badge bg-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Technologie associée">
                                                        <i class="fas fa-flask"></i>
                                                    </span>
                                                @endif
                                                @if($event->related_commander_id)
                                                    <span class="badge bg-info ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Commandant associé">
                                                        <i class="fas fa-user-astronaut"></i>
                                                    </span>
                                                @endif
                                                @if(count($event->consequences ?? []) > 0)
                                                    <span class="badge bg-warning text-dark ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Conséquences">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                    </span>
                                                @endif
                                                @if(count($event->possible_actions ?? []) > 0)
                                                    <span class="badge bg-success ms-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Actions possibles">
                                                        <i class="fas fa-tasks"></i>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-secondary">
                                    <i class="fas fa-info-circle"></i> Aucun événement à afficher.
                                </div>
                            </div>
                        @endforelse
                    </div>
                    
                    <!-- Pagination -->
                    @if($events->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $events->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/events.js') }}"></script>
@endpush
