@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')
@section('eyebrow', 'Directorio de clientes')

@section('content')
    <div class="page-heading">
        <div><h2>Directorio</h2><p>Registra y consulta la información de contacto.</p></div>
    </div>
    <form method="POST" action="{{ route('clientes.store') }}" class="surface form-panel row g-3">
        @csrf
        <div class="col-md-6"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" maxlength="50" value="{{ old('nombre') }}" autocomplete="given-name" required></div>
        <div class="col-md-6"><label class="form-label" for="apellido">Apellido</label><input class="form-control" id="apellido" name="apellido" maxlength="50" value="{{ old('apellido') }}" autocomplete="family-name" required></div>
        <div class="col-md-6"><label class="form-label" for="documento">Documento</label><input class="form-control" id="documento" name="documento" maxlength="20" value="{{ old('documento') }}" required></div>
        <div class="col-md-6"><label class="form-label" for="telefono">Teléfono</label><input class="form-control" id="telefono" name="telefono" maxlength="20" value="{{ old('telefono') }}" autocomplete="tel"></div>
        <div class="col-md-6"><label class="form-label" for="correo">Correo</label><input class="form-control" id="correo" name="correo" type="email" maxlength="100" value="{{ old('correo') }}" autocomplete="email"></div>
        <div class="col-12"><label class="form-label" for="direccion">Dirección</label><input class="form-control" id="direccion" name="direccion" maxlength="100" value="{{ old('direccion') }}"></div>
        <div class="col-12"><button class="btn btn-success" type="submit">Registrar cliente</button></div>
    </form>
    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Correo</th></tr></thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    <tr><td>{{ $cliente->nombre }} {{ $cliente->apellido }}</td><td>{{ $cliente->documento }}</td><td>{{ $cliente->telefono }}</td><td>{{ $cliente->correo }}</td></tr>
                @empty
                    <tr><td class="table-empty" colspan="4">No hay clientes registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $clientes->links() }}
@endsection
