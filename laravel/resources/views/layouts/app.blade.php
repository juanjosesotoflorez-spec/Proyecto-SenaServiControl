<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SenaServiControl')</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main-content">Saltar al contenido principal</a>
    @auth
        @php($rol = auth()->user()->rol->value)
        <div class="app-shell">
            <aside class="sidebar d-none d-lg-flex" aria-label="Navegación principal">
                <a class="brand" href="{{ route('dashboard') }}">
                    <span class="brand-mark" aria-hidden="true">SS</span>
                    <span><strong>SenaServi</strong><small>CONTROL</small></span>
                </a>
                <p class="sidebar-label">Espacio de trabajo</p>
                @include('layouts.partials.navigation')
                <div class="sidebar-footer"><span class="sidebar-footer-dot"></span>Operación activa</div>
            </aside>
            <div class="offcanvas offcanvas-start mobile-sidebar d-lg-none" tabindex="-1" id="mobile-navigation" aria-labelledby="mobile-navigation-title">
                <div class="offcanvas-header">
                    <div class="brand mobile-brand">
                        <span class="brand-mark" aria-hidden="true">SS</span>
                        <span><strong id="mobile-navigation-title">SenaServi</strong><small>CONTROL</small></span>
                    </div>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
                </div>
                <div class="offcanvas-body">
                    <p class="sidebar-label">Espacio de trabajo</p>
                    @include('layouts.partials.navigation')
                </div>
            </div>
            <div class="app-main">
                <header class="topbar">
                    <div class="topbar-heading">
                        <button class="btn btn-success mobile-menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-navigation" aria-controls="mobile-navigation" aria-label="Abrir menú de navegación">
                            <i class="fa-solid fa-bars" aria-hidden="true"></i>
                        </button>
                        <div>
                        <p class="topbar-eyebrow">@yield('eyebrow', 'Panel de operaciones')</p>
                        <h1>@yield('page-title', 'Resumen')</h1>
                        </div>
                    </div>
                    <form class="quick-search" role="search" method="GET" action="{{ route('servicios.index') }}">
                        <label class="visually-hidden" for="quick-search-input">Buscar servicios, placas o clientes</label>
                        <input class="form-control" id="quick-search-input" name="q" type="search" value="{{ request('q') }}" placeholder="Buscar servicios, placas o clientes">
                        <button class="btn btn-success" type="submit" aria-label="Buscar"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button>
                    </form>
                    <div class="dropdown profile-menu">
                        <button class="btn profile-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Abrir menú de perfil">
                            <span class="user-avatar" aria-hidden="true"><i class="fa-regular fa-user"></i></span>
                            <span class="profile-name">{{ auth()->user()->usuario }}</span>
                            <i class="fa-solid fa-chevron-down profile-chevron" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end profile-dropdown">
                            <li><span class="dropdown-item-text"><strong>{{ auth()->user()->usuario }}</strong><small>{{ ucfirst($rol) }}</small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2" aria-hidden="true"></i>Cerrar sesión</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </header>
                <main class="content-area" id="main-content">
    @else
        <div class="guest-shell @yield('page-class')" style="--auth-background-image: url('{{ asset('fondo.png') }}')">
            <header class="guest-header">
                <a class="brand brand-guest" href="{{ route('login') }}">
                    <span class="brand-mark" aria-hidden="true">SS</span>
                    <span><strong>SenaServi</strong><small>CONTROL</small></span>
                </a>
            </header>
            <main class="guest-main" id="main-content">
    @endauth
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="status" aria-live="polite">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="assertive" tabindex="-1">
                        <strong>Revisa la información del formulario</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar aviso"></button>
                    </div>
                @endif
                @yield('content')
            </main>
    @auth
        </div>
    @else
        </div>
    @endauth
</body>
</html>
