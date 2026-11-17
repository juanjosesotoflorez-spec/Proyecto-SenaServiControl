@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Búsqueda Multicriterio de Servicios</h2>

    <form method="GET" action="{{ route('busquedas.servicios') }}" class="row g-3 mb-4 card card-body">
        <div class="col-md-3">
            <input type="text" name="numero" value="{{ request('numero') }}" class="form-control" placeholder="N° Orden / Servicio">
        </div>
        <div class="col-md-3">
            <input type="text" name="placa" value="{{ request('placa') }}" class="form-control" placeholder="Placa Vehículo">
        </div>
        <div class="col-md-3">
            <input type="text" name="nombre" value="{{ request('nombre') }}" class="form-control" placeholder="Nombre Cliente">
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-primary">Filtrar Servicios</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Fecha</th>
                    <th>Vehículo</th>
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servicios as $servicio)
                    <tr>
                        <td>{{ $servicio->codigo_orden ?? $servicio->id }}</td>
                        <td>{{ $servicio->created_at->format('d/m/Y') }}</td>
                        <td>{{ $servicio->vehiculo->placa ?? 'N/A' }}</td>
                        <td>{{ $servicio->vehiculo->cliente->nombre ?? 'N/A' }}</td>
                        <td>{{ $servicio->tecnico->name ?? 'Sin asignar' }}</td>
                        <td><span class="badge bg-info text-dark">{{ $servicio->estado }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No se encontraron servicios con esos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $servicios->links() }}
</div>
@endsection