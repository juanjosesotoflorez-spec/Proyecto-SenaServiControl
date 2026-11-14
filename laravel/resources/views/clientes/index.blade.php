@extends('layouts.app')

@section('title', 'Clientes')
@section('page-title', 'Clientes')
@section('eyebrow', 'Directorio de clientes')

@section('content')
    <div class="page-heading">
        <div><h2>Directorio</h2><p>Registra y consulta la información de contacto.</p></div>
    </div>
    <form method="POST" action="{{ route('clientes.store') }}" class="surface form-panel row g-3" data-validate novalidate>
        @csrf
        <div class="col-md-6 form-field">
            <label class="form-label" for="nombre">Nombre</label>
            <input class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" type="text" minlength="2" maxlength="50" value="{{ old('nombre') }}" autocomplete="given-name" data-letters-only required aria-describedby="nombre-error">
            <div class="invalid-feedback @error('nombre') d-block @enderror" id="nombre-error" data-validation-feedback>@error('nombre'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="apellido">Apellido</label>
            <input class="form-control @error('apellido') is-invalid @enderror" id="apellido" name="apellido" type="text" minlength="2" maxlength="50" value="{{ old('apellido') }}" autocomplete="family-name" data-letters-only required aria-describedby="apellido-error">
            <div class="invalid-feedback @error('apellido') d-block @enderror" id="apellido-error" data-validation-feedback>@error('apellido'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="documento">Documento</label>
            <input class="form-control @error('documento') is-invalid @enderror" id="documento" name="documento" type="text" inputmode="numeric" minlength="6" maxlength="12" value="{{ old('documento') }}" data-digits-only required aria-describedby="documento-error">
            <div class="invalid-feedback @error('documento') d-block @enderror" id="documento-error" data-validation-feedback>@error('documento'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="telefono">Teléfono</label>
            <input class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" type="tel" inputmode="numeric" minlength="10" maxlength="10" value="{{ old('telefono') }}" autocomplete="tel" data-digits-only aria-describedby="telefono-error">
            <div class="invalid-feedback @error('telefono') d-block @enderror" id="telefono-error" data-validation-feedback>@error('telefono'){{ $message }}@enderror</div>
        </div>
        <div class="col-md-6 form-field">
            <label class="form-label" for="correo">Correo</label>
            <input class="form-control @error('correo') is-invalid @enderror" id="correo" name="correo" type="email" maxlength="100" value="{{ old('correo') }}" autocomplete="email" aria-describedby="correo-error">
            <div class="invalid-feedback @error('correo') d-block @enderror" id="correo-error" data-validation-feedback>@error('correo'){{ $message }}@enderror</div>
        </div>
        <div class="col-12 form-field">
            <label class="form-label" for="direccion">Dirección</label>
            <input class="form-control @error('direccion') is-invalid @enderror" id="direccion" name="direccion" type="text" minlength="5" maxlength="100" value="{{ old('direccion') }}" aria-describedby="direccion-error">
            <div class="invalid-feedback @error('direccion') d-block @enderror" id="direccion-error" data-validation-feedback>@error('direccion'){{ $message }}@enderror</div>
        </div>
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
