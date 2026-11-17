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

    @if (auth()->user()->rol->value === 'administrador')
        <div class="report-actions d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-success" href="{{ route('reportes.pdf') }}">Descargar reporte PDF</a>
            <a class="btn btn-outline-secondary" href="{{ route('reportes.pdf.print') }}" target="_blank" rel="noopener noreferrer">Imprimir reporte PDF</a>
        </div>
    @endif

    {{-- Filtros y Exportación Avanzada (RF-116 a RF-120) --}}
    <div class="surface card card-body mb-4">
        <form method="POST" action="{{ route('reportes.exportar') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label">Tipo de Reporte</label>
                <select name="tipo_reporte" class="form-select" required>
                    <option value="operativo">Operativo por Período</option>
                    <option value="productividad">Productividad por Técnico</option>
                    <option value="vehiculos">Estado de Vehículos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha Fin</label>
                <input type="date" name="fecha_fin" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Formato</label>
                <select name="formato" class="form-select" required>
                    <option value="pdf">PDF</option>
                    <option value="excel">Excel / CSV</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary">Exportar Reporte Filtrado</button>
            </div>
        </form>
    </div>

    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Vehículo</th>
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th>Estado</th>
                    <th>Costo</th>
                </tr>
            </thead>
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