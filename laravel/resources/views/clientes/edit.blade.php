@extends('layouts.app')

@section('title', 'Editar cliente')
@section('page-title', 'Editar cliente')
@section('eyebrow', 'Administración · Clientes')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Actualizar información</h2>
            <p>Edita los datos de {{ $cliente->nombre }} {{ $cliente->apellido }}. Si tiene una cuenta, su correo se actualizará también.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('clientes.index') }}"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Volver a clientes</a>
    </div>

    <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="surface form-panel admin-edit-form" data-validate novalidate>
        @csrf
        @method('PUT')
        <div class="admin-form-heading">
            <span class="admin-form-icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></span>
            <div><h2>Datos del cliente</h2><p>Los campos marcados son obligatorios.</p></div>
        </div>
        <div class="row g-3">
            @foreach ([
                ['nombre', 'Nombre', 'text', 50, 'given-name'],
                ['apellido', 'Apellido', 'text', 50, 'family-name'],
                ['documento', 'Documento', 'text', 12, null],
                ['telefono', 'Teléfono', 'tel', 10, 'tel'],
                ['correo', 'Correo electrónico', 'email', 100, 'email'],
                ['direccion', 'Dirección', 'text', 100, null],
            ] as [$field, $label, $type, $maxlength, $autocomplete])
                <div class="{{ $field === 'direccion' ? 'col-12' : 'col-md-6' }} form-field">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    <input
                        class="form-control @error($field) is-invalid @enderror"
                        id="{{ $field }}"
                        name="{{ $field }}"
                        type="{{ $type }}"
                        maxlength="{{ $maxlength }}"
                        value="{{ old($field, $cliente->{$field}) }}"
                        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                        @if (in_array($field, ['documento', 'telefono'], true)) inputmode="numeric" data-digits-only @endif
                        required
                        aria-describedby="{{ $field }}-error">
                    <div class="invalid-feedback @error($field) d-block @enderror" id="{{ $field }}-error" data-validation-feedback>@error($field){{ $message }}@enderror</div>
                </div>
            @endforeach
        </div>
        <div class="admin-form-actions">
            <a class="btn btn-outline-secondary" href="{{ route('clientes.index') }}">Cancelar</a>
            <button class="btn btn-success" type="submit"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Guardar cambios</button>
        </div>
    </form>
@endsection
