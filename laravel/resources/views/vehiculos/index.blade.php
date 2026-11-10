@extends('layouts.app')

@section('title', 'Vehículos')
@section('page-title', 'Vehículos')
@section('eyebrow', 'Parque automotor')

@section('content')
    <div class="page-heading">
        <div><h2>Vehículos registrados</h2><p>Administra placas y datos del parque automotor.</p></div>
    </div>
    <form method="POST" action="{{ route('vehiculos.store') }}" class="surface form-panel row g-3">
        @csrf
        <div class="col-md-6"><label class="form-label" for="placa">Placa</label><input class="form-control" id="placa" name="placa" maxlength="10" value="{{ old('placa') }}" required></div>
        <div class="col-md-6"><label class="form-label" for="marca">Marca</label><input class="form-control" id="marca" name="marca" maxlength="50" value="{{ old('marca') }}"></div>
        <div class="col-md-6"><label class="form-label" for="modelo">Modelo</label><input class="form-control" id="modelo" name="modelo" maxlength="50" value="{{ old('modelo') }}"></div>
        <div class="col-md-6"><label class="form-label" for="color">Color</label><input class="form-control" id="color" name="color" maxlength="30" value="{{ old('color') }}"></div>
        <div class="col-md-6"><label class="form-label" for="anio">Año</label><input class="form-control" id="anio" name="anio" type="number" min="1886" max="{{ (int) date('Y') + 1 }}" value="{{ old('anio') }}"></div>
        <div class="col-md-6"><label class="form-label" for="kilometraje">Kilometraje</label><input class="form-control" id="kilometraje" name="kilometraje" type="number" min="0" value="{{ old('kilometraje') }}"></div>
        @if (in_array($usuario->rol->value, ['administrador', 'recepcionista'], true))
            <div class="col-md-6">
                <label class="form-label" for="id_cliente">Cliente</label>
                <select class="form-select" id="id_cliente" name="id_cliente" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id_cliente }}" @selected(old('id_cliente') == $cliente->id_cliente)>{{ $cliente->nombre }} {{ $cliente->apellido }} · {{ $cliente->documento }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="col-12"><button class="btn btn-success" type="submit">Registrar vehículo</button></div>
    </form>
    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Placa</th><th>Marca</th><th>Modelo</th><th>Cliente</th></tr></thead>
            <tbody>
                @forelse ($vehiculos as $vehiculo)
                    <tr><td>{{ $vehiculo->placa }}</td><td>{{ $vehiculo->marca }}</td><td>{{ $vehiculo->modelo }}</td><td>{{ $vehiculo->cliente->nombre }} {{ $vehiculo->cliente->apellido }}</td></tr>
                @empty
                    <tr><td class="table-empty" colspan="4">No hay vehículos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $vehiculos->links() }}
@endsection
