@extends('layouts.app')

@section('title', 'Iniciar sesión')
@section('page-class', 'auth-background')

@section('content')
    <div class="auth-content">
        <section class="auth-copy" aria-label="Bienvenida">
            <span>Serviteca SENA</span>
            <h1>Bienvenido</h1>
            <p>Inicia sesión para consultar tus servicios y hacer seguimiento a tu vehículo.</p>
        </section>
        <section class="surface login-panel auth-login-panel" aria-labelledby="login-title">
            <div class="auth-logo-card">
                <img src="../../imagenes/sena_logo_transparente.png" alt="Logo SENA" width="48" height="48">
            </div>
            <h2 id="login-title">Iniciar sesión</h2>
            <p class="auth-login-intro">Accede al panel de operación del taller.</p>
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="correo">Correo</label>
                    <input class="form-control" id="correo" name="correo" type="email" value="{{ old('correo') }}" maxlength="100" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="contrasena">Contraseña</label>
                    <input class="form-control" id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-success w-100" type="submit">Entrar</button>
            </form>
            <p class="auth-login-link">¿No tienes cuenta? <a href="../../codigos_php/register.php">Regístrate</a></p>
            <p class="auth-login-link"><a href="../../index.html">Volver a la página principal</a></p>
        </section>
    </div>
@endsection
