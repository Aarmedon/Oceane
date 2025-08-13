@extends('layouts.app')

@section('title', 'Événement - ' . $event->title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reports.css') }}">
<style>
    .event-importance-1 { border-left: 4px solid #6c757d; }
    .event-importance-2 { border-left: 4px solid #0dcaf0; }
    .event-importance-3 { border-left: 4px solid #ffc107; }
    .event-importance-4 { border-left: 4px solid #fd7e14; }
    .event-importance-5 { border-left: 4px solid #dc3545; }
    
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
                    <li class="breadcrumb-item"><a href="{{ route('game.reports.events.index') }}">Événements</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $event->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card bg-dark border-secondary event-importance-{{ $event->importance }}">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="fas {{ $event->getIconClass() }}"></i> {{ $event->title }}
                        <span class="badge {{ $event->getBadgeClass() }} ms-2">{{ $event->getImportanceText() }}</span>
                    </h4>
                    <div>
                        <a href="{{ route('game.reports.events.index') }}" class="btn btn-sm btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="report-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex">
                                    <div class="event-icon me-3 {{ $event->getBadgeClass() }}">
                                        <i class="fas {{ $event->getIconClass() }}"></i>
                                    </div>
                                    <div>
                                        <h5>{{ $event->title }}</h5>
                                        <p class="text-muted">Tour {{ $event->turn_number }} - {{ $event->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-end">
                                    <p class="mb-1"><strong>Type:</strong> {{ $event->getTypeText() }}</p>
                                    <p><strong>Importance:</strong> {{ $event->getImportanceText() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-info-circle"></i> Description</h5>
                        <div class="card bg-dark border-secondary">
                            <div class="card-body">
                                <div class="event-description">
                                    {!! $event->description !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Éléments associés -->
                    <div class="report-section mb-4">
                        <h5><i class="fas fa-link"></i> Éléments associés</h5>
                        
                        <div class="row">
                            @if($event->planet_id)
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-dark border-secondary event-related-item">
                                        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fas fa-globe"></i> Planète</h6>
                                            <a href="{{ route('game.planets.show', $event->planet_id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <img src="{{ $event->getPlanet()->image_url ?? asset('images/planets/default.png') }}" 
                                                         alt="Image de la planète" 
                                                         class="rounded-circle" 
                                                         style="width: 48px; height: 48px;">
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $event->getPlanet()->name }}</h6>
                                                    <small class="text-muted">{{ $event->getPlanet()->type }} - {{ $event->getPlanet()->size }}km</small>
                                                    <div>
                                                        <span class="badge bg-info">{{ $event->getPlanet()->system_name }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($event->fleet_id)
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-dark border-secondary event-related-item">
                                        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fas fa-space-shuttle"></i> Flotte</h6>
                                            <a href="{{ route('game.fleets.show', $event->fleet_id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas fa-space-shuttle fa-lg"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $event->getFleet()->name }}</h6>
                                                    <small class="text-muted">{{ count($event->getFleet()->ships) }} vaisseaux</small>
                                                    <div>
                                                        <span class="badge bg-info">{{ $event->getFleet()->location }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($event->technology_id)
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-dark border-secondary event-related-item">
                                        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fas fa-flask"></i> Technologie</h6>
                                            <a href="{{ route('game.technologies.show', $event->technology_id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                        <i class="fas {{ $event->getTechnology()->icon ?? 'fa-flask' }} fa-lg"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $event->getTechnology()->name }}</h6>
                                                    <small class="text-muted">Niveau {{ $event->getTechnology()->level }}</small>
                                                    <div>
                                                        <span class="badge bg-info">{{ $event->getTechnology()->category }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            @if($event->related_commander_id)
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-dark border-secondary event-related-item">
                                        <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0"><i class="fas fa-user-astronaut"></i> Commandant</h6>
                                            <a href="{{ route('game.commanders.show', $event->related_commander_id) }}" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <img src="{{ $event->getRelatedCommander()->avatar ?? asset('images/avatars/default.png') }}" 
                                                         alt="Avatar du commandant" 
                                                         class="rounded-circle" 
                                                         style="width: 48px; height: 48px;">
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $event->getRelatedCommander()->name }}</h6>
                                                    <small class="text-muted">{{ $event->getRelatedCommander()->faction }}</small>
                                                    <div>
                                                        <span class="badge {{ $event->getRelatedCommander()->getRelationBadgeClass() }}">
                                                            {{ $event->getRelatedCommander()->getRelationText() }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        @if(!$event->planet_id && !$event->fleet_id && !$event->technology_id && !$event->related_commander_id)
                            <div class="alert alert-secondary">
                                <i class="fas fa-info-circle"></i> Aucun élément associé à cet événement.
                            </div>
                        @endif
                    </div>
                    
                    <!-- Conséquences -->
                    @if(count($event->consequences ?? []) > 0)
                        <div class="report-section mb-4">
                            <h5><i class="fas fa-exclamation-circle"></i> Conséquences</h5>
                            
                            <div class="event-timeline">
                                @foreach($event->consequences as $consequence)
                                    <div class="timeline-item {{ $consequence['importance'] >= 4 ? 'important' : '' }}">
                                        <div class="card bg-dark border-secondary">
                                            <div class="card-body">
                                                <h6>{{ $consequence['title'] }}</h6>
                                                <p>{{ $consequence['description'] }}</p>
                                                
                                                @if(isset($consequence['effects']) && count($consequence['effects']) > 0)
                                                    <div class="mt-2">
                                                        <h6 class="text-muted">Effets:</h6>
                                                        <ul class="list-group list-group-flush bg-transparent">
                                                            @foreach($consequence['effects'] as $effect)
                                                                <li class="list-group-item bg-dark text-light border-secondary">
                                                                    <i class="fas {{ $effect['icon'] ?? 'fa-arrow-right' }} me-2 {{ $effect['positive'] ? 'text-success' : 'text-danger' }}"></i>
                                                                    {{ $effect['text'] }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Actions possibles -->
                    @if(count($event->possible_actions ?? []) > 0)
                        <div class="report-section">
                            <h5><i class="fas fa-tasks"></i> Actions possibles</h5>
                            
                            <div class="row">
                                @foreach($event->possible_actions as $action)
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-dark border-secondary">
                                            <div class="card-body">
                                                <h6>{{ $action['title'] }}</h6>
                                                <p>{{ $action['description'] }}</p>
                                                
                                                <div class="d-flex justify-content-between align-items-center mt-3">
                                                    <div>
                                                        <span class="badge bg-info me-2">
                                                            <i class="fas fa-clock"></i> {{ $action['time_cost'] ?? 'Immédiat' }}
                                                        </span>
                                                        @if(isset($action['resource_cost']))
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-coins"></i> {{ $action['resource_cost'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <a href="{{ $action['action_url'] }}" class="btn btn-primary btn-sm">
                                                        <i class="fas {{ $action['icon'] ?? 'fa-check' }}"></i> {{ $action['button_text'] ?? 'Exécuter' }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                
                <div class="card-footer text-end">
                    <a href="{{ route('game.reports.events.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour aux événements
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($event->additional_data) && count($event->additional_data) > 0)
<!-- Élément caché contenant les données JSON pour le JavaScript -->
<script type="application/json" id="event-data">
    {!! json_encode($event->additional_data) !!}
</script>
@endif

@endsection

@push('scripts')
<script src="{{ asset('js/events.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Marquer l'événement comme lu via AJAX si ce n'est pas déjà fait
    if (!{{ $event->read ? 'true' : 'false' }}) {
        markEventAsRead({{ $event->id }});
    }
});
</script>
@endpush
