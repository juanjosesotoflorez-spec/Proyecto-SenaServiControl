<?php
@extends('layouts.app')

@section('title', 'Vehículos')
@section('page-class', 'page-vehiculos')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Vehículos</h2>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Volver</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Registrar vehículo</h5>

                <form method="POST" action="{{ route('vehiculos.store') }}" novalidate>
                    @csrf

                    <div class="row g-3">
                        @if ($usuario->rol === App\Enums\RolUsuario::Administrador || $usuario->rol === App\Enums\RolUsuario::Recepcionista)
                            <div class="col-md-12">
                                <label for="id_cliente" class="form-label">Cliente</label>
                                <select class="form-select @error('id_cliente') is-invalid @enderror" id="id_cliente" name="id_cliente">
                                    <option value="">Selecciona un cliente</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->id_cliente }}" {{ old('id_cliente') == $cliente->id_cliente ? 'selected' : '' }}>
                                            {{ $cliente->nombre }} {{ $cliente->apellido }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_cliente')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label for="placa" class="form-label">Placa</label>
                            <input type="text" class="form-control @error('placa') is-invalid @enderror" id="placa" name="placa" value="{{ old('placa') }}" required>
                            @error('placa')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control @error('marca') is-invalid @enderror" id="marca" name="marca" value="{{ old('marca') }}">
                            @error('marca')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control @error('modelo') is-invalid @enderror" id="modelo" name="modelo" value="{{ old('modelo') }}">
                            @error('modelo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="color" class="form-label">Color</label>
                            <input type="text" class="form-control @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color') }}">
                            @error('color')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="anio" class="form-label">Año</label>
                            <input type="number" class="form-control @error('anio') is-invalid @enderror" id="anio" name="anio" value="{{ old('anio') }}">
                            @error('anio')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="kilometraje" class="form-label">Kilometraje</label>
                            <input type="number" class="form-control @error('kilometraje') is-invalid @enderror" id="kilometraje" name="kilometraje" value="{{ old('kilometraje') }}">
                            @error('kilometraje')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">Guardar vehículo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Listado de vehículos</h5>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Placa</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Color</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehiculos as $vehiculo)
                                <tr>
                                    <td>{{ $vehiculo->cliente?->nombre ?? 'N/A' }} {{ $vehiculo->cliente?->apellido ?? '' }}</td>
                                    <td>{{ $vehiculo->placa }}</td>
                                    <td>{{ $vehiculo->marca ?? 'N/A' }}</td>
                                    <td>{{ $vehiculo->modelo ?? 'N/A' }}</td>
                                    <td>{{ $vehiculo->color ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay vehículos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $vehiculos->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection