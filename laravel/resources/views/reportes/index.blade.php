@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes')
@section('eyebrow', 'Información del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Reporte de servicios</h2>
            <p>Consulta los ingresos, responsables y estado de cada servicio.</p>
        </div>
    </div>
    <div class="report-actions d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-success" href="{{ route('reportes.pdf') }}">Descargar reporte PDF</a>
        <a class="btn btn-outline-secondary" href="{{ route('reportes.pdf.print') }}" target="_blank" rel="noopener noreferrer">Imprimir reporte PDF</a>
    </div>
    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Vehículo</th><th>Cliente</th><th>Técnico</th><th>Estado</th><th>Costo</th></tr></thead>
            <tbody>
                @forelse ($servicios as $servicio)
                    <tr>
                        <td>{{ $servicio->id_servicio }}</td>
                        <td>{{ $servicio->fecha_ingreso->format('d/m/Y') }}</td>
                        <td>{{ $servicio->tipo_servicio }}</td>
                        <td>{{ $servicio->vehiculo->placa }}</td>
                        <td>{{ $servicio->vehiculo->cliente->nombre }} {{ $servicio->vehiculo->cliente->apellido }}</td>
                        <td>{{ $servicio->tecnico->nombre }} {{ $servicio->tecnico->apellido }}</td>
                        <td><x-service-status :status="$servicio->estado" /></td>
                        <td>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="8">No hay servicios para incluir en el reporte.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $servicios->links() }}
@endsection