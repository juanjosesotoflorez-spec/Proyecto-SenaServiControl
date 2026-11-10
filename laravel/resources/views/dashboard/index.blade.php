@extends('layouts.app')

@section('title', 'Panel de control')
@section('page-title', 'Resumen de operaciones')
@section('eyebrow', 'Bienvenido, ' . $usuario->usuario)

@section('content')
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
        <article class="col-12 col-sm-6 col-lg-3">
            <div class="surface metric-card h-100">
            <span class="metric-label">{{ $esPersonal ? 'Ingresos registrados del mes' : 'Ingresos del mes' }}</span>
            @if ($esPersonal)
                <strong class="metric-value">${{ number_format((float) $ingresosMes, 0, ',', '.') }}</strong>
                <span class="metric-note">Según fecha de ingreso del servicio</span>
            @else
                <strong class="metric-value">—</strong>
                <span class="metric-note">Indicador disponible para administración y recepción</span>
            @endif
            </div>
        </article>
        <article class="col-12 col-sm-6 col-lg-3">
            <div class="surface metric-card h-100">
            <span class="metric-label">Clientes registrados</span>
            @if ($esPersonal)
                <strong class="metric-value">{{ number_format($clientesRegistrados) }}</strong>
                <span class="metric-note">En la base de clientes</span>
            @else
                <strong class="metric-value">—</strong>
                <span class="metric-note">Indicador disponible para administración y recepción</span>
            @endif
            </div>
        </article>
        <article class="col-12 col-sm-6 col-lg-3">
            <div class="surface metric-card h-100">
                <span class="metric-label">Servicios entregados</span>
                <strong class="metric-value">{{ number_format($serviciosEntregados) }}</strong>
                <span class="metric-note">{{ $esPersonal ? 'Total del taller' : 'En tus servicios' }}</span>
            </div>
        </article>
    </section>

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
