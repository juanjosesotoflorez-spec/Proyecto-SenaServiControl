@extends('layouts.app')

@section('title', 'Vehículos')
@section('page-title', 'Vehículos')
@section('eyebrow', 'Parque automotor')

@section('content')
    <div class="page-heading">
        <div><h2>Vehículos registrados</h2><p>Administra placas y datos del parque automotor.</p></div>
    </div>
    <form method="POST" action="{{ route('vehiculos.store') }}" class="surface form-panel row g-3" data-validate novalidate>
        @csrf
        <div class="col-md-6 form-field">
            <label class="form-label" for="placa">Placa</label>
            <input class="form-control @error('placa') is-invalid @enderror" id="placa" name="placa" type="text" minlength="6" maxlength="6" pattern="[A-Z0-9]{6}" value="{{ old('placa') }}" data-uppercase required aria-describedby="placa-error">
            <div class="invalid-feedback @error('placa') d-block @enderror" id="placa-error" data-validation-feedback>@error('placa'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="marca">Marca</label>
            <input class="form-control @error('marca') is-invalid @enderror" id="marca" name="marca" type="text" minlength="2" maxlength="40" value="{{ old('marca') }}" aria-describedby="marca-error">
            <div class="invalid-feedback @error('marca') d-block @enderror" id="marca-error" data-validation-feedback>@error('marca'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="modelo">Modelo</label>
            <input class="form-control @error('modelo') is-invalid @enderror" id="modelo" name="modelo" type="text" minlength="2" maxlength="40" value="{{ old('modelo') }}" aria-describedby="modelo-error">
            <div class="invalid-feedback @error('modelo') d-block @enderror" id="modelo-error" data-validation-feedback>@error('modelo'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="color">Color</label>
            <input class="form-control @error('color') is-invalid @enderror" id="color" name="color" type="text" maxlength="30" value="{{ old('color') }}" aria-describedby="color-error">
            <div class="invalid-feedback @error('color') d-block @enderror" id="color-error" data-validation-feedback>@error('color'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="anio">Año</label>
            <input class="form-control @error('anio') is-invalid @enderror" id="anio" name="anio" type="text" inputmode="numeric" minlength="4" maxlength="4" min="1950" max="{{ (int) date('Y') + 1 }}" value="{{ old('anio') }}" data-digits-only aria-describedby="anio-error">
            <div class="invalid-feedback @error('anio') d-block @enderror" id="anio-error" data-validation-feedback>@error('anio'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="kilometraje">Kilometraje</label>
            <input class="form-control @error('kilometraje') is-invalid @enderror" id="kilometraje" name="kilometraje" type="text" inputmode="numeric" maxlength="10" min="0" max="4294967295" value="{{ old('kilometraje') }}" data-digits-only aria-describedby="kilometraje-error">
            <div class="invalid-feedback @error('kilometraje') d-block @enderror" id="kilometraje-error" data-validation-feedback>@error('kilometraje'){{ $message }}@enderror</div>
        </div>
        @if (in_array($usuario->rol->value, ['administrador', 'recepcionista'], true))
            <div class="col-md-6 form-field">
                <label class="form-label" for="id_cliente">Cliente</label>
                <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id_cliente }}" @selected(old('id_cliente') == $cliente->id_cliente)>{{ $cliente->nombre }} {{ $cliente->apellido }} · {{ $cliente->documento }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('id_cliente') d-block @enderror" data-validation-feedback>@error('id_cliente'){{ $message }}@enderror</div>
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
