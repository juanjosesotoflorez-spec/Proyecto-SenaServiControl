@extends('layouts.app')

@section('title', 'Panel de control')
@section('page-title', 'Resumen de operaciones')
@section('eyebrow', 'Bienvenido, ' . $usuario->usuario)

@section('content')
    @if ($perfilFaltante)
        <div class="alert alert-warning" role="alert">
            Tu cuenta no tiene un perfil de {{ $perfilFaltante }} vinculado. Comunícate con un administrador para completar la configuración; mientras tanto, no se mostrarán servicios asociados.
        </div>
    @endif

    <div class="page-heading">
        <div>
            <h2>Indicadores clave</h2>
            <p>Actividad registrada en SenaServiControl.</p>
        </div>
    </div>

    <section class="row g-3 metric-grid" aria-label="Métricas de operación">
        <article class="col-12 col-sm-6 col-lg-3">
            <div class="surface metric-card metric-card-accent h-100">
            <span class="metric-label">Servicios activos</span>
            <strong class="metric-value">{{ number_format($serviciosActivos) }}</strong>
            <span class="metric-note">Pendientes o en proceso</span>
            </div>
        </article>
        @if ($usuario->rol->value === 'cliente')
            <article class="col-12 col-sm-6 col-lg-3">
                <div class="surface metric-card h-100">
                    <span class="metric-label">Mis vehículos</span>
                    <strong class="metric-value">{{ number_format($misVehiculos) }}</strong>
                    <span class="metric-note">Vehículos asociados a tu perfil</span>
                </div>
            </article>
            <article class="col-12 col-sm-6 col-lg-3">
                <div class="surface metric-card h-100">
                    <span class="metric-label">Solicitudes pendientes</span>
                    <strong class="metric-value">{{ number_format($solicitudesPendientes) }}</strong>
                    <span class="metric-note">Esperando respuesta del taller</span>
                </div>
            </article>
        @else
            <article class="col-12 col-sm-6 col-lg-3">
                <div class="surface metric-card h-100">
                    <span class="metric-label">{{ $usuario->rol->value === 'tecnico' ? 'Ingresos de tus servicios este mes' : 'Ingresos del mes' }}</span>
                    @if ($puedeVerIndicadores)
                        <strong class="metric-value">${{ number_format((float) $ingresosMes, 0, ',', '.') }}</strong>
                        <span class="metric-note">{{ $usuario->rol->value === 'tecnico' ? 'Calculado sobre tus servicios asignados' : 'Según fecha de ingreso del servicio' }}</span>
                    @else
                        <strong class="metric-value">—</strong>
                        <span class="metric-note">Indicador disponible para administración y recepción</span>
                    @endif
                </div>
            </article>
            <article class="col-12 col-sm-6 col-lg-3">
                <div class="surface metric-card h-100">
                    <span class="metric-label">Clientes registrados</span>
                    @if ($puedeVerIndicadores)
                        <strong class="metric-value">{{ number_format($clientesRegistrados) }}</strong>
                        <span class="metric-note">En la base de clientes</span>
                    @else
                        <strong class="metric-value">—</strong>
                        <span class="metric-note">Indicador disponible para administración y recepción</span>
                    @endif
                </div>
            </article>
        @endif
        <article class="col-12 col-sm-6 col-lg-3">
            <div class="surface metric-card h-100">
                <span class="metric-label">Servicios entregados</span>
                <strong class="metric-value">{{ number_format($serviciosEntregados) }}</strong>
                <span class="metric-note">{{ $esPersonal ? 'Total del taller' : 'En tus servicios' }}</span>
            </div>
        </article>
    </section>

    @if ($usuario->rol->value === 'administrador')
        <section class="row g-3 dashboard-charts mb-4" aria-label="Gráficas de servicios">
            <div class="col-12 col-xl-5">
                <article class="surface h-100 p-3">
                    <h2 class="h6 mb-3">Servicios por estado</h2>
                    <div class="dashboard-chart-container">
                        <canvas
                            data-dashboard-chart="states"
                            data-labels="{{ json_encode($graficas['estados']['etiquetas'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                            data-values="{{ json_encode($graficas['estados']['valores']) }}"
                            role="img"
                            aria-label="Cantidad de servicios agrupados por estado"></canvas>
                    </div>
                </article>
            </div>
            <div class="col-12 col-xl-7">
                <article class="surface h-100 p-3">
                    <h2 class="h6 mb-3">Ingresos y servicios por mes</h2>
                    <div class="dashboard-chart-container">
                        <canvas
                            data-dashboard-chart="monthly"
                            data-labels="{{ json_encode($graficas['mensual']['etiquetas'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
                            data-values="{{ json_encode($graficas['mensual']['ingresos']) }}"
                            data-services="{{ json_encode($graficas['mensual']['servicios']) }}"
                            role="img"
                            aria-label="Ingresos y cantidad de servicios de los últimos seis meses"></canvas>
                    </div>
                </article>
            </div>
        </section>
    @endif

    <div class="page-heading">
        <div>
            <h2>Servicios recientes</h2>
            <p>Últimos ingresos registrados en el taller.</p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('servicios.index') }}">Ver servicios</a>
        @if ($usuario->rol->value === 'tecnico')
            <a class="btn btn-success btn-sm" href="{{ route('solicitudes.index') }}">Ver solicitudes por aceptar</a>
        @elseif ($usuario->rol->value === 'cliente')
            <a class="btn btn-success btn-sm" href="{{ route('solicitudes.index') }}">Solicitar un servicio</a>
        @endif
    </div>
    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Servicio</th><th>Fecha</th><th>Vehículo</th><th>Cliente</th><th>Estado</th><th>Costo</th></tr></thead>
            <tbody>
                @forelse ($serviciosRecientes as $servicio)
                    <tr>
                        <td>#{{ $servicio->id_servicio }} · {{ $servicio->tipo_servicio }}</td>
                        <td>{{ $servicio->fecha_ingreso->format('d/m/Y') }}</td>
                        <td>{{ $servicio->vehiculo->placa }}</td>
                        <td>{{ $servicio->vehiculo->cliente->nombre }} {{ $servicio->vehiculo->cliente->apellido }}</td>
                        <td><x-service-status :status="$servicio->estado" /></td>
                        <td>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="6">Todavía no hay servicios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
