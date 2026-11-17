@extends('layouts.app')

@section('title', 'Panel administrativo')
@section('page-title', 'Panel administrativo')
@section('eyebrow', 'Analítica del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Indicadores del taller</h2>
            <p>Resumen de servicios, ingresos, retrasos e incidencias.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.monitoreo') }}">Retrasos</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.incidencias') }}">Incidencias</a>
            <a class="btn btn-outline-secondary" href="{{ route('admin.auditoria') }}">Auditoría</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($estados as $nombre => $total)
            <div class="col-6 col-lg-3">
                <div class="surface p-3 h-100"><small class="text-muted">{{ $nombre }}</small><div class="h3 mb-0">{{ $total }}</div></div>
            </div>
        @endforeach
        <div class="col-6 col-lg-4">
            <div class="surface p-3 h-100"><small class="text-muted">Ingresos (servicios entregados)</small><div class="h3 mb-0">${{ number_format($ingresos, 0, ',', '.') }}</div></div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="surface p-3 h-100"><small class="text-muted">Servicios atrasados</small><div class="h3 mb-0">{{ $atrasados }}</div></div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="surface p-3 h-100"><small class="text-muted">Incidencias sin resolver</small><div class="h3 mb-0">{{ $incidenciasAbiertas }}</div></div>
        </div>
    </div>

    <div class="surface p-3">
        <h3 class="h6 mb-3">Carga de trabajo por técnico (servicios activos)</h3>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Técnico</th><th>Servicios activos</th></tr></thead>
                <tbody>
                    @forelse ($cargaTecnicos as $fila)
                        <tr><td>{{ $fila->nombre }} {{ $fila->apellido }}</td><td>{{ $fila->total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-muted">No hay servicios activos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection