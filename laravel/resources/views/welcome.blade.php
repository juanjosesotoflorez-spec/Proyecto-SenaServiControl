@extends('layouts.app')

@section('title', 'SenaServiControl')

@section('content')
    <div class="surface login-panel">
        <h1>SenaServiControl</h1>
        <p>Gestión de servicios, vehículos y clientes del taller.</p>
        <a class="btn btn-success w-100" href="{{ route('login') }}">Iniciar sesión</a>
    </div>
@endsection