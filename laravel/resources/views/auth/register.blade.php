@extends('layouts.app')

@section('title', 'Crear cuenta')
@section('page-class', 'auth-background')

@section('content')
    <div class="auth-content">
        <section class="surface login-panel auth-login-panel auth-register-panel" aria-labelledby="register-title">
            <div class="auth-logo-card">
                <img src="../../imagenes/sena_logo_transparente.png" alt="Logo SENA" width="48" height="48">
            </div>
            <h2 id="register-title">Crear cuenta</h2>
            <p class="auth-login-intro">Completa tus datos para registrarte como cliente.</p>
            <form method="POST" action="{{ route('register.store') }}" data-validate novalidate>
                @csrf
                <div class="row g-2">
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="usuario">Nombre de usuario</label>
                        <input class="form-control @error('usuario') is-invalid @enderror" id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" minlength="3" maxlength="50" autocomplete="username" required autofocus>
                        <div class="invalid-feedback @error('usuario') d-block @enderror" data-validation-feedback>@error('usuario'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="correo">Correo</label>
                        <input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" maxlength="100" autocomplete="email" required aria-describedby="registro-correo-error">
                        <div class="invalid-feedback @error('correo') d-block @enderror" id="registro-correo-error" data-validation-feedback>@error('correo'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" minlength="2" maxlength="50" autocomplete="given-name" data-letters-only required aria-describedby="registro-nombre-error">
                        <div class="invalid-feedback @error('nombre') d-block @enderror" id="registro-nombre-error" data-validation-feedback>@error('nombre'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="apellido">Apellido</label>
                        <input class="form-control @error('apellido') is-invalid @enderror" id="apellido" name="apellido" type="text" value="{{ old('apellido') }}" minlength="2" maxlength="50" autocomplete="family-name" data-letters-only required aria-describedby="registro-apellido-error">
                        <div class="invalid-feedback @error('apellido') d-block @enderror" id="registro-apellido-error" data-validation-feedback>@error('apellido'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="documento">Documento</label>
                        <input class="form-control @error('documento') is-invalid @enderror" id="documento" name="documento" type="text" inputmode="numeric" value="{{ old('documento') }}" minlength="6" maxlength="12" autocomplete="off" data-digits-only required aria-describedby="registro-documento-error">
                        <div class="invalid-feedback @error('documento') d-block @enderror" id="registro-documento-error" data-validation-feedback>@error('documento'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" type="tel" inputmode="numeric" value="{{ old('telefono') }}" minlength="10" maxlength="10" autocomplete="tel" data-digits-only aria-describedby="registro-telefono-error">
                        <div class="invalid-feedback @error('telefono') d-block @enderror" id="registro-telefono-error" data-validation-feedback>@error('telefono'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 form-field">
                        <label class="form-label" for="direccion">Dirección</label>
                        <input class="form-control @error('direccion') is-invalid @enderror" id="direccion" name="direccion" type="text" value="{{ old('direccion') }}" minlength="5" maxlength="100" autocomplete="street-address" aria-describedby="registro-direccion-error">
                        <div class="invalid-feedback @error('direccion') d-block @enderror" id="registro-direccion-error" data-validation-feedback>@error('direccion'){{ $message }}@enderror</div>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="contrasena">Contraseña</label>
                        <input class="form-control @error('contrasena') is-invalid @enderror" id="contrasena" name="contrasena" type="password" minlength="8" maxlength="255" autocomplete="new-password" data-password-policy required aria-describedby="registro-contrasena-error registro-contrasena-requisitos">
                        <div class="invalid-feedback @error('contrasena') d-block @enderror" id="registro-contrasena-error" data-validation-feedback>@error('contrasena'){{ $message }}@enderror</div>
                        <ul class="password-requirements" id="registro-contrasena-requisitos" aria-label="Requisitos de la contraseña">
                            <li data-password-rule="length">Al menos 8 caracteres</li>
                            <li data-password-rule="uppercase">Una letra mayúscula</li>
                            <li data-password-rule="numbers">Al menos dos números</li>
                            <li data-password-rule="special">Un carácter especial (por ejemplo: !, @ o #)</li>
                        </ul>
                    </div>
                    <div class="col-12 col-sm-6 form-field">
                        <label class="form-label" for="contrasena_confirmation">Confirmar contraseña</label>
                        <input class="form-control" id="contrasena_confirmation" name="contrasena_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                        <div class="invalid-feedback" data-validation-feedback></div>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success w-100" type="submit">Registrarse</button>
                    </div>
                </div>
            </form>
            <p class="auth-login-link">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
            <p class="auth-login-link"><a href="../../index.html">Volver a la página principal</a></p>
        </section>
        <aside class="auth-side-message" aria-label="Serviteca SENA">
            <span>Serviteca SENA</span>
            <p>Tu camino sigue con el mejor cuidado.</p>
        </aside>
    </div>
@endsection
