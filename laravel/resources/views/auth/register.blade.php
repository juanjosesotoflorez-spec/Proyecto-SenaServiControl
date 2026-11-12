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
            <form method="POST" action="{{ route('register.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="usuario">Nombre de usuario</label>
                        <input class="form-control" id="usuario" name="usuario" type="text" value="{{ old('usuario') }}" minlength="3" maxlength="50" autocomplete="username" required autofocus>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="correo">Correo</label>
                        <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" maxlength="100" autocomplete="email" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" maxlength="50" autocomplete="given-name" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="apellido">Apellido</label>
                        <input class="form-control" id="apellido" name="apellido" type="text" value="{{ old('apellido') }}" maxlength="50" autocomplete="family-name" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="documento">Documento</label>
                        <input class="form-control" id="documento" name="documento" type="text" value="{{ old('documento') }}" maxlength="20" autocomplete="off" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input class="form-control" id="telefono" name="telefono" type="tel" value="{{ old('telefono') }}" maxlength="20" autocomplete="tel">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="direccion">Dirección</label>
                        <input class="form-control" id="direccion" name="direccion" type="text" value="{{ old('direccion') }}" maxlength="100" autocomplete="street-address">
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="contrasena">Contraseña</label>
                        <input class="form-control" id="contrasena" name="contrasena" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label" for="contrasena_confirmation">Confirmar contraseña</label>
                        <input class="form-control" id="contrasena_confirmation" name="contrasena_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
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
