<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'Océane') }} - @yield('title', 'Bienvenue')</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <!-- Vite assets -->
    @vite(['resources/js/app.js'])
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom CSS -->
    @stack('styles')
</head>
<body class="bg-dark text-light">
    <div id="app">
        <!-- Navigation principale -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom border-secondary">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="Océane Logo" height="40">
                    {{ config('app.name', 'Océane') }}
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Menu de gauche -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            @if(Auth::user()->commanders()->exists())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('game.dashboard') }}">
                                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('game.galaxy_map') }}">
                                        <i class="fas fa-globe"></i> Carte galactique
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('game.fleets') }}">
                                        <i class="fas fa-rocket"></i> Flottes
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('game.technologies') }}">
                                        <i class="fas fa-microchip"></i> Technologies
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('game.orders') }}">
                                        <i class="fas fa-tasks"></i> Ordres
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('game.reports') }}">
                                        <i class="fas fa-file-alt"></i> Rapports
                                        @if(Auth::user()->commanders()->first()->turnReports()->where('is_read', false)->count() > 0)
                                            <span class="badge bg-danger">
                                                {{ Auth::user()->commanders()->first()->turnReports()->where('is_read', false)->count() }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endif

                            @php
                                $gmEmails = array_map('strtolower', config('oceane.admin.gamemasters_emails', []));
                                $isGmEmail = in_array(strtolower((string) Auth::user()->email), $gmEmails, true);
                            @endphp
                            @if(Auth::user()->is_admin || $isGmEmail)
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-tools"></i> Administration
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                                <i class="fas fa-home"></i> Dashboard admin
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.races.index') }}">
                                                <i class="fas fa-dna"></i> Races
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.fleets.index') }}">
                                                <i class="fas fa-rocket"></i> Flottes
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.univers') }}">
                                                <i class="fas fa-globe"></i> Univers
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Menu de droite -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Liens d'authentification -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Connexion') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Inscription') }}</a>
                                </li>
                            @endif
                        @else
                            <!-- Informations du commandant -->
                            @if(Auth::user()->commanders()->exists())
                                <li class="nav-item pe-3">
                                    <span class="nav-link">
                                        <i class="fas fa-user-circle"></i> 
                                        {{ Auth::user()->commanders()->first()->name }}
                                    </span>
                                </li>
                                <li class="nav-item pe-3">
                                    <span class="nav-link">
                                        <i class="fas fa-coins"></i> 
                                        {{ number_format(Auth::user()->commanders()->first()->credits, 0) }} cr
                                    </span>
                                </li>
                                <li class="nav-item pe-3">
                                    <span class="nav-link">
                                        <i class="fas fa-calendar-alt"></i> 
                                        Tour {{ (int) (\App\Models\GameState::query()->value('current_turn') ?? 1) }}
                                    </span>
                                </li>
                            @endif
                            
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    @php
                                        $gmEmails = array_map('strtolower', config('oceane.admin.gamemasters_emails', []));
                                        $isGmEmail = in_array(strtolower((string) Auth::user()->email), $gmEmails, true);
                                    @endphp
                                    @if(Auth::user()->is_admin || $isGmEmail)
                                        <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                            <i class="fas fa-tools"></i> Administration
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    @endif
                                    
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        <i class="fas fa-user-edit"></i> Profil
                                    </a>
                                    
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt"></i> {{ __('Déconnexion') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Messages flash -->
        <div class="container mt-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        <!-- Contenu principal -->
        <main class="py-4">
            @yield('content')
        </main>
        
        <!-- Pied de page -->
        <footer class="mt-5 py-4 bg-dark text-light border-top border-secondary">
            <div class="container text-center">
                <p>{{ config('app.name', 'Océane') }} &copy; {{ date('Y') }} - Tous droits réservés</p>
                <p class="text-muted small">Version {{ config('oceane.game.version', '1.0.0') }}</p>
            </div>
        </footer>
    </div>
    
    <!-- Custom Scripts -->
    @stack('scripts')
</body>
</html>
