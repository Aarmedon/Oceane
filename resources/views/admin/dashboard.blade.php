@extends('layouts.app')

@section('title', 'Administration')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Administration – Maître du Jeu</h1>
            <p class="text-muted mb-0">Tour actuel: <strong>{{ $currentTurn }}</strong> • Intervalle entre tours: {{ $hoursBetweenTurns }}h</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card bg-secondary bg-opacity-25 border-secondary">
                <div class="card-body">
                    <h5 class="card-title">Résolution du tour</h5>
                    <p class="card-text">Déclenche la résolution complète du tour côté serveur.</p>
                    <form method="POST" action="{{ route('admin.resolve-turn') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-hourglass-start"></i> Lancer la résolution du tour
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card bg-secondary bg-opacity-25 border-secondary">
                <div class="card-body">
                    <h5 class="card-title">Accès MJ</h5>
                    <p class="card-text small text-muted mb-0">Accès réservé aux emails configurés dans <code>GAMEMASTERS</code> (.env).</p>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-secondary">
                <div class="card-body d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.races.index') }}" class="btn btn-outline-light">
                        <i class="fas fa-dna"></i> Gérer les races
                    </a>
                    <a href="{{ route('admin.fleets.index') }}" class="btn btn-outline-light">
                        <i class="fas fa-rocket"></i> Flottes
                    </a>
                    <a href="{{ route('admin.univers') }}" class="btn btn-outline-light">
                        <i class="fas fa-globe"></i> Univers (vue)
                    </a>
                    @auth
                        @if(Auth::user()->commanders()->exists())
                            <a href="{{ route('game.dashboard') }}" class="btn btn-outline-info">
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord joueur
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
