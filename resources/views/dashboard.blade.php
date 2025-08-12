@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark border-secondary">
                <div class="card-header bg-dark border-secondary">
                    <h4 class="mb-0">Tableau de bord</h4>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p>Bienvenue, {{ $user->name }} !</p>
                    
                    @if ($hasCommander)
                        <div class="alert alert-info">
                            <p>Vous avez déjà un commandant. Accédez au jeu pour continuer votre aventure.</p>
                            <a href="{{ route('game.dashboard') }}" class="btn btn-primary mt-2">
                                <i class="fas fa-rocket"></i> Accéder au jeu
                            </a>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <p>Vous n'avez pas encore de commandant. Créez-en un pour commencer à jouer.</p>
                            <a href="{{ route('game.create_commander') }}" class="btn btn-primary mt-2">
                                <i class="fas fa-user-plus"></i> Créer un commandant
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
