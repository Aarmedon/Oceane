@extends('layouts.app')

@section('title', 'Créer un commandant')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h4><i class="fas fa-user-plus"></i> Créer votre commandant</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Bienvenue dans Océane ! Avant de commencer votre aventure galactique, vous devez créer votre commandant.
                    </div>

                    <form action="{{ route('game.store_commander') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="name" class="form-label">Nom du commandant</label>
                            <input type="text" class="form-control bg-dark text-light border-secondary @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Le nom de votre commandant doit être unique et représentera votre identité dans l'univers d'Océane.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Choisissez votre race</label>
                            <div class="row">
                                @foreach($races as $race)
                                    <div class="col-md-4 mb-3">
                                        <div class="card bg-dark border-secondary h-100">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="race_id" id="race_{{ $race->id }}" value="{{ $race->id }}" {{ old('race_id') == $race->id ? 'checked' : '' }} required>
                                                <label class="form-check-label" for="race_{{ $race->id }}">
                                                    <div class="card-body text-center">
                                                        <img src="{{ asset('images/races/' . $race->id . '.png') }}" alt="{{ $race->name }}" class="img-fluid mb-2" style="max-height: 100px;">
                                                        <h5 class="card-title">{{ $race->name }}</h5>
                                                        <p class="card-text small">{{ Str::limit($race->description, 100) }}</p>
                                                    </div>
                                                </label>
                                            </div>
                                            <div class="card-footer bg-dark">
                                                <button type="button" class="btn btn-sm btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#raceModal{{ $race->id }}">
                                                    Détails
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Modal pour les détails de la race -->
                                    <div class="modal fade" id="raceModal{{ $race->id }}" tabindex="-1" aria-labelledby="raceModalLabel{{ $race->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content bg-dark text-light border-secondary">
                                                <div class="modal-header bg-secondary">
                                                    <h5 class="modal-title" id="raceModalLabel{{ $race->id }}">{{ $race->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-4 text-center">
                                                            <img src="{{ asset('images/races/' . $race->id . '.png') }}" alt="{{ $race->name }}" class="img-fluid mb-3" style="max-height: 200px;">
                                                        </div>
                                                        <div class="col-md-8">
                                                            <p>{{ $race->description }}</p>
                                                            
                                                            <h6>Bonus raciaux:</h6>
                                                            <ul>
                                                                @if($race->bonus_shipbuilding)
                                                                    <li><strong>Construction navale:</strong> +{{ $race->bonus_shipbuilding }}%</li>
                                                                @endif
                                                                @if($race->bonus_research)
                                                                    <li><strong>Recherche:</strong> +{{ $race->bonus_research }}%</li>
                                                                @endif
                                                                @if($race->bonus_commerce)
                                                                    <li><strong>Commerce:</strong> +{{ $race->bonus_commerce }}%</li>
                                                                @endif
                                                                @if($race->bonus_production)
                                                                    <li><strong>Production:</strong> +{{ $race->bonus_production }}%</li>
                                                                @endif
                                                                @if($race->bonus_population)
                                                                    <li><strong>Croissance de population:</strong> +{{ $race->bonus_population }}%</li>
                                                                @endif
                                                                @if($race->bonus_diplomacy)
                                                                    <li><strong>Diplomatie:</strong> +{{ $race->bonus_diplomacy }}%</li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="document.getElementById('race_{{ $race->id }}').checked = true;">Choisir cette race</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('race_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Biographie (optionnelle)</label>
                            <textarea class="form-control bg-dark text-light border-secondary @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted">
                                Décrivez votre commandant, son histoire, et ses ambitions dans l'univers d'Océane.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Attention : La création d'un commandant est définitive et ne peut être annulée. Chaque joueur ne peut créer qu'un seul commandant.
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-space-shuttle"></i> Lancer votre aventure
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Retour à l'accueil
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
