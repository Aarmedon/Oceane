@extends('layouts.game')

@section('title', 'Création d\'un ordre - ' . $orderTypeTitle)

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('game.dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('game.orders.index') }}">Centre de commandement</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nouvel ordre: {{ $orderTypeTitle }}</li>
                </ol>
            </nav>
            
            <div class="card bg-dark text-light border-secondary mb-4">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="mb-0">
                        <i class="fas {{ $orderTypeIcon }}"></i>
                        Création d'un ordre: {{ $orderTypeTitle }}
                    </h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form method="POST" action="{{ route('game.orders.store', ['type' => $orderType]) }}" class="needs-validation" novalidate>
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-secondary text-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Informations sur l'ordre</h6>
                                        <p class="card-text">
                                            <strong>Type:</strong> {{ $orderTypeTitle }}<br>
                                            <strong>Tour actuel:</strong> {{ $currentTurn }}<br>
                                            <strong>Exécution prévue:</strong> Tour {{ $currentTurn + 1 }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-dark border-secondary">
                                    <div class="card-body">
                                        <h6 class="card-title">Instructions</h6>
                                        <p class="card-text">{{ $instructions }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @yield('order_form')
                        
                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <a href="{{ route('game.orders.index') }}" class="btn btn-secondary me-2">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-primary submit-btn">
                                    <i class="fas fa-paper-plane"></i> Soumettre l'ordre
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('css/orders.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('js/orders.js') }}"></script>
@yield('order_scripts')
@endpush
