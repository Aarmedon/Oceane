@extends('layouts.app')

@section('title', 'Administration – Univers')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col d-flex align-items-center justify-content-between">
            <h1 class="h3 mb-0">Administration – Univers</h1>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Tableau d'admin
            </a>
        </div>
    </div>

    @isset($stats)
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="card bg-secondary bg-opacity-25 border-secondary">
                <div class="card-body">
                    <div class="text-muted small">Galaxies</div>
                    <div class="h4 mb-0">{{ $stats['galaxy_count'] ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-secondary bg-opacity-25 border-secondary">
                <div class="card-body">
                    <div class="text-muted small">Secteurs</div>
                    <div class="h4 mb-0">{{ $stats['sector_count'] ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-secondary bg-opacity-25 border-secondary">
                <div class="card-body">
                    <div class="text-muted small">Systèmes</div>
                    <div class="h4 mb-0">{{ $stats['star_system_count'] ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-secondary bg-opacity-25 border-secondary">
                <div class="card-body">
                    <div class="text-muted small">Planètes</div>
                    <div class="h4 mb-0">{{ $stats['planet_count'] ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info">Vue d'index de l'univers (placeholder). Les vues détaillées seront ajoutées au fur et à mesure.</div>
    @endisset

    <div class="row mt-4">
        <div class="col-12">
            <h2 class="h5 mb-3"><i class="fas fa-globe"></i> Carte galactique (MJ)</h2>
            <div class="alert alert-secondary bg-opacity-25 text-light">
                Le widget de carte legacy a été retiré. Utilisez la nouvelle carte Vue 3 pour la vue MJ.
                <a href="{{ route('game.galaxy_map') }}?admin=1" class="btn btn-sm btn-outline-light ms-2">
                    Ouvrir la carte Vue 3 (admin)
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
