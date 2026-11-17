@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-3">Búsqueda Multicriterio de Clientes</h2>

    <form method="GET" action="{{ route('busquedas.clientes') }}" class="row g-3 mb-4 card card-body">
        <div class="col-md-3">
            <input type="text" name="documento" value="{{ request('documento') }}" class="form-control" placeholder="Documento">
        </div>
        <div class="col-md-4">
            <input type="text" name="nombre" value="{{ request('nombre') }}" class="form-control" placeholder="Nombre del cliente">
        </div>
        <div class="col-md-3">
            <input type="text" name="telefono" value="{{ request('telefono') }}" class="form-control" placeholder="Teléfono">
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                    <tr>
                        <td>{{ $cliente->documento }}</td>
                        <td>{{ $cliente->nombre }}</td>
                        <td>{{ $cliente->telefono }}</td>
                        <td>{{ $cliente->email ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No se encontraron clientes con esos criterios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $clientes->links() }}
</div>
@endsection