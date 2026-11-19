@extends('layouts.app')

@section('title', 'Editar vehículo')
@section('page-title', 'Editar vehículo')
@section('eyebrow', 'Administración · Vehículos')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Actualizar información</h2>
            <p>Edita los datos del vehículo {{ $vehiculo->placa }} sin afectar su historial de servicios.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('vehiculos.index') }}"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Volver a vehículos</a>
    </div>

    <form method="POST" action="{{ route('vehiculos.update', $vehiculo) }}" class="surface form-panel admin-edit-form" data-validate novalidate>
        @csrf
        @method('PUT')
        <div class="admin-form-heading">
            <span class="admin-form-icon"><i class="fa-solid fa-car-side" aria-hidden="true"></i></span>
            <div><h2>Ficha del vehículo</h2><p>Los cambios no eliminan ni modifican las órdenes existentes.</p></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6 form-field">
                <label class="form-label" for="placa">Placa</label>
                <input class="form-control @error('placa') is-invalid @enderror" id="placa" name="placa" type="text" minlength="6" maxlength="6" pattern="[A-Z0-9]{6}" value="{{ old('placa', $vehiculo->placa) }}" data-uppercase required aria-describedby="placa-error">
                <div class="invalid-feedback @error('placa') d-block @enderror" id="placa-error" data-validation-feedback>@error('placa'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="id_cliente">Propietario</label>
                <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id_cliente }}" @selected(old('id_cliente', $vehiculo->id_cliente) == $cliente->id_cliente)>{{ $cliente->nombre }} {{ $cliente->apellido }} · {{ $cliente->documento }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('id_cliente') d-block @enderror" data-validation-feedback>@error('id_cliente'){{ $message }}@enderror</div>
            </div>
            @foreach ([
                ['marca', 'Marca', 40],
                ['modelo', 'Modelo', 40],
                ['color', 'Color', 30],
                ['anio', 'Año', 4],
                ['kilometraje', 'Kilometraje', 10],
            ] as [$field, $label, $maxlength])
                <div class="col-md-6 form-field">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input
                        class="form-control @error($field) is-invalid @enderror"
                        id="{{ $field }}"
                        name="{{ $field }}"
                        type="text"
                        maxlength="{{ $maxlength }}"
                        value="{{ old($field, $vehiculo->{$field}) }}"
                        @if (in_array($field, ['anio', 'kilometraje'], true)) inputmode="numeric" data-digits-only @endif
                        required
                        aria-describedby="{{ $field }}-error">
                    <div class="invalid-feedback @error($field) d-block @enderror" id="{{ $field }}-error" data-validation-feedback>@error($field){{ $message }}@enderror</div>
                </div>
            @endforeach
        </div>
        <div class="admin-form-actions">
            <a class="btn btn-outline-secondary" href="{{ route('vehiculos.index') }}">Cancelar</a>
            <button class="btn btn-success" type="submit"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Guardar cambios</button>
        </div>
    </form>
@endsection
