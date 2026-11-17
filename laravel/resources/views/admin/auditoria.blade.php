@extends('layouts.app')

@section('title', 'Auditoría')
@section('page-title', 'Auditoría')
@section('eyebrow', 'Control del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Historial de actividad</h2>
            <p>Registro de solo lectura de las acciones de los usuarios. No se puede editar ni eliminar.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Volver al panel</a>
    </div>

    <form method="GET" action="{{ route('admin.auditoria') }}" class="surface form-panel row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label" for="entidad">Entidad</label>
            <select class="form-select" id="entidad" name="entidad">
                <option value="">Todas</option>
                @foreach ($entidades as $entidad)
                    <option value="{{ $entidad }}" @selected(($filtros['entidad'] ?? null) === $entidad)>{{ $entidad }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="desde">Desde</label>
            <input class="form-control @error('desde') is-invalid @enderror" id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="hasta">Hasta</label>
            <input class="form-control @error('hasta') is-invalid @enderror" id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}">
        </div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-success w-100" type="submit">Filtrar</button></div>
    </form>

    <div class="surface p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Usuario</th><th>Entidad</th><th>Acción</th><th>Servicio</th><th>Descripción</th></tr></thead>
                <tbody>
                    @forelse ($actividad as $registro)
                        <tr>
                            <td>{{ $registro->fecha_hora?->format('d/m/Y H:i') }}</td>
                            <td>{{ $registro->usuario?->usuario }}</td>
                            <td>{{ $registro->entidad }}</td>
                            <td>{{ $registro->accion }}</td>
                            <td>{{ $registro->id_servicio ? '#'.$registro->id_servicio : '—' }}</td>
                            <td>{{ $registro->descripcion }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No hay actividad para los filtros seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $actividad->links() }}
    </div>
@endsection