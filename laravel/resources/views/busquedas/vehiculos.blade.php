@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Búsqueda Multicriterio de Vehículos</h2>

    <form method="GET" action="{{ route('busquedas.vehiculos') }}" class="row g-3 mb-4 card card-body">
        <div class="col-md-3">
            <input type="text" name="placa" value="{{ request('placa') }}" class="form-control" placeholder="Placa (Ej: ABC123)">
        </div>
        <div class="col-md-3">
            <input type="text" name="marca" value="{{ request('marca') }}" class="form-control" placeholder="Marca">
        </div>
        <div class="col-md-4">
            <input type="text" name="modelo" value="{{ request('modelo') }}" class="form-control" placeholder="Modelo">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Placa</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Propietario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehiculos as $vehiculo)
                    <tr>
                        <td><strong>{{ $vehiculo->placa }}</strong></td>
                        <td>{{ $vehiculo->marca }}</td>
                        <td>{{ $vehiculo->modelo }}</td>
                        <td>{{ $vehiculo->cliente->nombre ?? 'Sin propietario' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No se encontraron vehículos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $vehiculos->links() }}
</div>
@endsection