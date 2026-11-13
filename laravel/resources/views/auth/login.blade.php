@extends('layouts.app')

@section('title', 'Iniciar sesión')
@section('page-class', 'auth-background')

@section('content')
    <div class="auth-content">
        <section class="surface login-panel auth-login-panel" aria-labelledby="login-title">
            <div class="auth-logo-card">
                <img src="../../imagenes/sena_logo_transparente.png" alt="Logo SENA" width="48" height="48">
            </div>
            <h2 id="login-title">Iniciar sesión</h2>
            <p class="auth-login-intro">Accede al panel de operación del taller.</p>
            <form method="POST" action="{{ route('login.store') }}" data-validate novalidate>
                @csrf
                <div class="mb-3 form-field">
                    <label class="form-label" for="correo">Correo</label>
                    <input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" value="{{ old('correo') }}" maxlength="100" autocomplete="username" required autofocus aria-describedby="correo-error">
                    <div class="invalid-feedback @error('correo') d-block @enderror" id="correo-error" data-validation-feedback>@error('correo'){{ $message }}@enderror</div>
                </div>
                <div class="mb-3 form-field">
                    <label class="form-label" for="contrasena">Contraseña</label>
                    <input class="form-control @error('contrasena') is-invalid @enderror" id="contrasena" name="contrasena" type="password" maxlength="255" autocomplete="current-password" required aria-describedby="contrasena-error">
                    <div class="invalid-feedback @error('contrasena') d-block @enderror" id="contrasena-error" data-validation-feedback>@error('contrasena'){{ $message }}@enderror</div>
                </div>
                <button class="btn btn-success w-100" type="submit">Entrar</button>
            </form>
            <p class="auth-login-link">¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate</a></p>
            <p class="auth-login-link"><a href="../../index.html">Volver a la página principal</a></p>
        </section>
        <aside class="auth-side-message" aria-label="Serviteca SENA">
            <span>Serviteca SENA</span>
            <p>Tu camino sigue con el mejor cuidado.</p>
        </aside>
    </div>
@endsection
