@extends('layouts.app')

@section('title', 'Monitoreo de retrasos')
@section('page-title', 'Monitoreo de retrasos')
@section('eyebrow', 'Analítica del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Servicios con entrega vencida</h2>
            <p>Servicios sin finalizar cuya fecha estimada de entrega ya pasó.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Volver al panel</a>
    </div>

    <div class="surface p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>#</th><th>Placa</th><th>Cliente</th><th>Técnico</th><th>Estado</th><th>Entrega estimada</th><th>Días de retraso</th></tr></thead>
                <tbody>
                    @forelse ($servicios as $servicio)
                        <tr>
                            <td><a href="{{ route('servicios.show', $servicio) }}">{{ $servicio->id_servicio }}</a></td>
                            <td>{{ $servicio->vehiculo->placa }}</td>
                            <td>{{ $servicio->vehiculo->cliente->nombre }} {{ $servicio->vehiculo->cliente->apellido }}</td>
                            <td>{{ $servicio->tecnico?->nombre }} {{ $servicio->tecnico?->apellido }}</td>
                            <td><span class="badge {{ $servicio->estado?->badgeClass() }}">{{ $servicio->estado?->value }}</span></td>
                            <td>{{ $servicio->fecha_entrega_estimada->format('d/m/Y') }}</td>
                            <td>{{ (int) $servicio->fecha_entrega_estimada->diffInDays(today()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No hay servicios atrasados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $servicios->links() }}
    </div>
@endsection