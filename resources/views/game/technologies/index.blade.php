@extends('layouts.app')

@section('title', 'Centre de Recherche')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/technologies.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Centre de Recherche</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-flask"></i> Centre de Recherche</h4>
                    <div>
                        <span class="badge bg-info">
                            <i class="fas fa-microscope"></i> Points de recherche: {{ number_format($researchPoints, 0) }}
                        </span>
                    </div>
                </div>
                
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            @if($currentResearch)
                                <div class="alert alert-info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>Recherche en cours:</strong> 
                                            {{ $currentResearch->technology->name }} (Niveau {{ $currentResearch->pivot->level + 1 }})
                                        </div>
                                        <div>
                                            <form action="{{ route('game.technologies.cancel', $currentResearch->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette recherche ?')">
                                                    <i class="fas fa-times"></i> Annuler
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 20px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                            role="progressbar" 
                                            style="width: {{ $currentResearch->pivot->research_progress }}%">
                                            {{ $currentResearch->pivot->research_progress }}%
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between small mt-1">
                                        <span>Coût total: {{ number_format($currentResearch->getResearchCostForLevel($currentResearch->pivot->level + 1), 0) }} points</span>
                                        <span>Achèvement estimé: Tour {{ $estimatedCompletionTurn }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-secondary">
                                    <i class="fas fa-info-circle"></i> Aucune recherche en cours. Sélectionnez une technologie ci-dessous pour commencer à la rechercher.
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Filtres de catégories -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="btn-group tech-filter-group">
                                <button class="btn btn-outline-light active" data-filter="all">
                                    Toutes les catégories
                                </button>
                                @foreach($categories as $category)
                                    <button class="btn btn-outline-light" data-filter="{{ $category }}">
                                        {{ ucfirst($category) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- Arbre technologique -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="tech-tree-container">
                                <div class="tech-tree" id="techTree">
                                    <!-- L'arbre technologique sera généré par JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des technologies par catégorie -->
    @foreach($technologies->groupBy('category') as $category => $techGroup)
        <div class="row mb-4 tech-category" data-category="{{ $category }}">
            <div class="col-md-12">
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-secondary text-white">
                        <h5>{{ ucfirst($category) }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($techGroup as $tech)
                                <div class="col-md-4 mb-3">
                                    <div class="card bg-dark border-secondary h-100 tech-card {{ $tech->isResearchable ? '' : 'tech-locked' }}" 
                                        data-tech-id="{{ $tech->id }}" 
                                        data-category="{{ $tech->category }}">
                                        <div class="card-header bg-dark text-light d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">{{ $tech->name }}</h6>
                                            @if($tech->pivot && $tech->pivot->level > 0)
                                                <span class="badge bg-success">Niveau {{ $tech->pivot->level }}/{{ $tech->max_level }}</span>
                                            @elseif($tech->isResearchable)
                                                <span class="badge bg-info">Disponible</span>
                                            @else
                                                <span class="badge bg-secondary">Verrouillé</span>
                                            @endif
                                        </div>
                                        <div class="card-body">
                                            <div class="tech-icon mb-2" style="background-image: url('/images/technologies/{{ $tech->image_path ?? 'default.jpg' }}')"></div>
                                            
                                            <p class="small text-muted mb-2">{{ Str::limit($tech->description, 100) }}</p>
                                            
                                            @if($tech->prerequisiteTechnology->id)
                                                <div class="tech-prerequisite small">
                                                    <strong>Prérequis:</strong> {{ $tech->prerequisiteTechnology->name }} (Niveau {{ $tech->prerequisite_level }})
                                                </div>
                                            @endif
                                            
                                            <div class="tech-benefits small mt-2">
                                                <strong>Bénéfices:</strong>
                                                <ul class="ps-3 mb-0">
                                                    @if($tech->shipComponents->count() > 0)
                                                        <li>Débloque {{ $tech->shipComponents->count() }} composant(s) de vaisseau</li>
                                                    @endif
                                                    @if($tech->buildings->count() > 0)
                                                        <li>Débloque {{ $tech->buildings->count() }} bâtiment(s)</li>
                                                    @endif
                                                    @if($tech->dependentTechnologies->count() > 0)
                                                        <li>Débloque {{ $tech->dependentTechnologies->count() }} technologie(s)</li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-dark">
                                            @if($tech->pivot && $tech->pivot->level >= $tech->max_level)
                                                <div class="d-grid">
                                                    <button class="btn btn-sm btn-success" disabled>
                                                        <i class="fas fa-check"></i> Niveau maximum atteint
                                                    </button>
                                                </div>
                                            @elseif($tech->isResearchable && !$currentResearch)
                                                <form action="{{ route('game.technologies.research', $tech->id) }}" method="POST">
                                                    @csrf
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span>Coût:</span>
                                                        <strong>{{ number_format($tech->getResearchCostForLevel($tech->pivot ? $tech->pivot->level + 1 : 1), 0) }} points</strong>
                                                    </div>
                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-microscope"></i> Rechercher
                                                        </button>
                                                    </div>
                                                </form>
                                            @elseif(!$tech->isResearchable)
                                                <div class="d-grid">
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="fas fa-lock"></i> Prérequis manquants
                                                    </button>
                                                </div>
                                            @else
                                                <div class="d-grid">
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>
                                                        <i class="fas fa-clock"></i> Recherche en cours
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Modal de détails de technologie -->
<div class="modal fade" id="techDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="techDetailsTitle">Détails de la technologie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="techDetailsContent">
                <!-- Le contenu sera chargé dynamiquement par JavaScript -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="researchTechButton" style="display: none;">
                    <i class="fas fa-microscope"></i> Rechercher
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Élément caché contenant les données JSON des technologies pour le JavaScript -->
<script type="application/json" id="technologies-data">
    {!! json_encode([
        'technologies' => $technologies->map(function($tech) {
            return [
                'id' => $tech->id,
                'name' => $tech->name,
                'category' => $tech->category,
                'level' => $tech->pivot ? $tech->pivot->level : 0,
                'max_level' => $tech->max_level,
                'prerequisite_id' => $tech->prerequisite_technology_id,
                'prerequisite_level' => $tech->prerequisite_level,
                'is_researchable' => $tech->isResearchable,
                'research_cost' => $tech->getResearchCostForLevel($tech->pivot ? $tech->pivot->level + 1 : 1),
                'image_path' => $tech->image_path ?? 'default.jpg'
            ];
        }),
        'current_research' => $currentResearch ? [
            'id' => $currentResearch->id,
            'progress' => $currentResearch->pivot->research_progress
        ] : null,
        'research_points' => $researchPoints
    ]) !!}
</script>

@endsection

@push('scripts')
<script src="{{ asset('js/technologies.js') }}"></script>
@endpush
