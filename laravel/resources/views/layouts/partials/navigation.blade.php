<nav class="sidebar-nav" aria-label="Navegación del sistema">
    <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
        <i class="fa-solid fa-chart-line" aria-hidden="true"></i><span>Resumen</span>
    </a>
    <a class="sidebar-link {{ request()->routeIs('servicios.*') ? 'is-active' : '' }}" href="{{ route('servicios.index') }}">
        <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i><span>Servicios</span>
    </a>
    @if (in_array($rol, ['administrador', 'recepcionista', 'tecnico'], true))
        <a class="sidebar-link {{ request()->routeIs('solicitudes.*') ? 'is-active' : '' }}" href="{{ route('solicitudes.index') }}">
            <i class="fa-solid fa-inbox" aria-hidden="true"></i><span>Solicitudes</span>
        </a>
    @elseif ($rol === 'cliente')
        <a class="sidebar-link {{ request()->routeIs('solicitudes.*') ? 'is-active' : '' }}" href="{{ route('solicitudes.index') }}">
            <i class="fa-solid fa-calendar-plus" aria-hidden="true"></i><span>Solicitar servicio</span>
        </a>
    @endif
    @if (in_array($rol, ['administrador', 'recepcionista', 'cliente'], true))
        <a class="sidebar-link {{ request()->routeIs('vehiculos.*') ? 'is-active' : '' }}" href="{{ route('vehiculos.index') }}">
            <i class="fa-solid fa-car" aria-hidden="true"></i><span>Vehículos</span>
        </a>
    @endif
    @if (in_array($rol, ['administrador', 'recepcionista'], true))
        <a class="sidebar-link {{ request()->routeIs('clientes.*') ? 'is-active' : '' }}" href="{{ route('clientes.index') }}">
            <i class="fa-solid fa-users" aria-hidden="true"></i><span>Clientes</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('reportes.*') ? 'is-active' : '' }}" href="{{ route('reportes.index') }}">
            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i><span>Reportes</span>
        </a>
    @endif
</nav>