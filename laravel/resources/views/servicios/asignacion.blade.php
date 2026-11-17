@extends('layouts.app')

@section('title', 'Asignación de técnico')
@section('page-title', 'Asignación de técnico')
@section('eyebrow', 'Servicio #'.$servicio->id_servicio)

@section('content')
    <div class="page-heading">
        <div>
            <h2>{{ $servicio->tipo_servicio }} · {{ $servicio->vehiculo->placa }}</h2>
            <p>Técnico actual: {{ $servicio->tecnico?->nombre }} {{ $servicio->tecnico?->apellido }} · Estado: {{ $servicio->estado?->value }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('servicios.index') }}">Volver a servicios</a>
    </div>

    <form method="POST" action="{{ route('servicios.asignacion.store', $servicio) }}" class="surface form-panel row g-3" novalidate>
        @csrf
        <div class="col-md-6">
            <label class="form-label" for="id_tecnico_nuevo">Nuevo técnico responsable</label>
            <select class="form-select @error('id_tecnico_nuevo') is-invalid @enderror" id="id_tecnico_nuevo" name="id_tecnico_nuevo" required>
                <option value="">Selecciona un técnico</option>
                @foreach ($tecnicos as $tecnico)
                    <option value="{{ $tecnico->id_tecnico }}" @selected((int) old('id_tecnico_nuevo') === $tecnico->id_tecnico)>
                        {{ $tecnico->apellido }} {{ $tecnico->nombre }}@if ($tecnico->especialidad) — {{ $tecnico->especialidad }}@endif
                    </option>
                @endforeach
            </select>
            @error('id_tecnico_nuevo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="motivo">Motivo</label>
            <input class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" type="text" maxlength="500" value="{{ old('motivo') }}" required>
            @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <button class="btn btn-success" type="submit"><i class="fa-solid fa-user-gear me-1" aria-hidden="true"></i>Asignar técnico</button>
        </div>
    </form>

    <div class="surface mt-4 p-3">
        <h3 class="h6 mb-3">Historial de asignaciones</h3>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Fecha</th><th>Anterior</th><th>Nuevo</th><th>Motivo</th><th>Aceptación</th></tr></thead>
                <tbody>
                    @forelse ($servicio->asignacionesTecnico as $asignacion)
                        <tr>
                            <td>{{ $asignacion->fecha_asignacion?->format('d/m/Y H:i') }}</td>
                            <td>{{ $asignacion->tecnicoAnterior ? $asignacion->tecnicoAnterior->nombre.' '.$asignacion->tecnicoAnterior->apellido : '—' }}</td>
                            <td>{{ $asignacion->tecnicoNuevo?->nombre }} {{ $asignacion->tecnicoNuevo?->apellido }}</td>
                            <td>{{ $asignacion->motivo }}</td>
                            <td>{{ $asignacion->estado_aceptacion }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">Aún no hay asignaciones registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection