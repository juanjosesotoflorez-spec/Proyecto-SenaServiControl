@extends('layouts.app')

@section('title', 'Administrar cuenta')
@section('page-title', 'Administrar cuenta')
@section('eyebrow', 'Control de acceso · Usuario #' . $usuario->id_usuario)

@section('content')
    <div class="page-heading">
        <div><h2>Editar {{ $usuario->usuario }}</h2><p>Actualiza acceso y datos de inicio de sesión.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('usuarios.index') }}"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Volver al directorio</a>
    </div>

    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="surface form-panel admin-edit-form" data-validate novalidate>
        @csrf
        @method('PUT')
        <div class="admin-form-heading">
            <span class="admin-form-icon"><i class="fa-solid fa-user-gear" aria-hidden="true"></i></span>
            <div><h2>Datos de la cuenta</h2><p>Las contraseñas no se muestran ni se modifican desde este formulario.</p></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6 form-field">
                <label class="form-label" for="usuario">Nombre de usuario</label>
                <input class="form-control @error('usuario') is-invalid @enderror" id="usuario" name="usuario" type="text" minlength="3" maxlength="50" value="{{ old('usuario', $usuario->usuario) }}" required aria-describedby="usuario-error">
                <div class="invalid-feedback @error('usuario') d-block @enderror" id="usuario-error" data-validation-feedback>@error('usuario'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="correo">Correo electrónico</label>
                <input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" maxlength="100" value="{{ old('correo', $usuario->correo) }}" required aria-describedby="correo-error">
                <div class="invalid-feedback @error('correo') d-block @enderror" id="correo-error" data-validation-feedback>@error('correo'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="rol">Rol del sistema</label>
                <select class="form-select @error('rol') is-invalid @enderror" id="rol" name="rol" required>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->value }}" @selected(old('rol', $usuario->rol->value) === $rol->value)>{{ ucfirst($rol->value) }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('rol') d-block @enderror" data-validation-feedback>@error('rol'){{ $message }}@enderror</div>
                <small class="admin-field-hint">Un cambio de rol solo se admite si ya existe exclusivamente la ficha del rol de destino.</small>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="estado">Estado de acceso</label>
                <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                    @foreach ($estados as $estado)
                        <option value="{{ $estado->value }}" @selected(old('estado', $usuario->estado->value) === $estado->value)>{{ ucfirst($estado->value) }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('estado') d-block @enderror" data-validation-feedback>@error('estado'){{ $message }}@enderror</div>
                <small class="admin-field-hint">Una cuenta inactiva no podrá iniciar sesión.</small>
            </div>
        </div>
        <div class="admin-form-actions">
            <a class="btn btn-outline-secondary" href="{{ route('usuarios.index') }}">Cancelar</a>
            <button class="btn btn-success" type="submit"><i class="fa-solid fa-check me-2" aria-hidden="true"></i>Guardar cambios</button>
        </div>
    </form>
@endsection
