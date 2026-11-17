
@extends('layouts.app')

@section('title', 'Clientes')
@section('page-class', 'page-clientes')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Clientes</h2>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Volver</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Registrar cliente</h5>

                <form method="POST" action="{{ route('clientes.store') }}" novalidate>
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                            @error('nombre')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control @error('apellido') is-invalid @enderror" id="apellido" name="apellido" value="{{ old('apellido') }}" required>
                            @error('apellido')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="documento" class="form-label">Documento</label>
                            <input type="text" class="form-control @error('documento') is-invalid @enderror" id="documento" name="documento" value="{{ old('documento') }}" required>
                            @error('documento')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono') }}">
                            @error('telefono')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control @error('direccion') is-invalid @enderror" id="direccion" name="direccion" value="{{ old('direccion') }}">
                            @error('direccion')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" value="{{ old('correo') }}">
                            @error('correo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-success">Guardar cliente</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Listado de clientes</h5>

                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Documento</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clientes as $cliente)
                                <tr>
                                    <td>{{ $cliente->nombre }}</td>
                                    <td>{{ $cliente->apellido }}</td>
                                    <td>{{ $cliente->documento }}</td>
                                    <td>{{ $cliente->telefono ?? 'N/A' }}</td>
                                    <td>{{ $cliente->correo ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No hay clientes registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $clientes->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection