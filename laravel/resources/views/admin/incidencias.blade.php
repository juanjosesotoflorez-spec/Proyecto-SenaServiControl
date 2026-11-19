@extends('layouts.app')

@section('title', 'Incidencias')
@section('page-title', 'Incidencias')
@section('eyebrow', 'Control del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Reporte y atención de incidencias</h2>
            <p>Registra problemas del taller y haz seguimiento hasta su resolución.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.incidencias.store') }}" class="surface form-panel row g-3 mb-4" novalidate>
        @csrf
        <div class="col-md-4">
            <label class="form-label" for="id_servicio">Servicio relacionado (opcional)</label>
            <select class="form-select @error('id_servicio') is-invalid @enderror" id="id_servicio" name="id_servicio">
                <option value="">Ninguno</option>
                @foreach ($servicios as $servicio)
                    <option value="{{ $servicio->id_servicio }}" @selected((int) old('id_servicio') === $servicio->id_servicio)>#{{ $servicio->id_servicio }} · {{ $servicio->tipo_servicio }}</option>
                @endforeach
            </select>
            @error('id_servicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="2" required>{{ old('descripcion') }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12"><button class="btn btn-success" type="submit">Reportar incidencia</button></div>
    </form>

    <form method="GET" action="{{ route('admin.incidencias') }}" class="mb-3 d-flex gap-2">
        <select class="form-select w-auto" name="estado" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            @foreach ($estados as $nombre)
                <option value="{{ $nombre }}" @selected($estado === $nombre)>{{ $nombre }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary" type="submit">Filtrar</button>
    </form>

    <div class="surface p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>#</th><th>Fecha</th><th>Reporta</th><th>Servicio</th><th>Descripción</th><th>Estado</th>@if ($esAdmin)<th>Atender</th>@endif</tr></thead>
                <tbody>
                    @forelse ($incidencias as $incidencia)
                        <tr>
                            <td>{{ $incidencia->id_incidencia }}</td>
                            <td>{{ $incidencia->fecha_registro?->format('d/m/Y H:i') }}</td>
                            <td>{{ $incidencia->usuarioReporta?->usuario }}</td>
                            <td>{{ $incidencia->id_servicio ? '#'.$incidencia->id_servicio : '—' }}</td>
                            <td>{{ $incidencia->descripcion }}@if ($incidencia->nota_atencion)<br><small class="text-muted">Nota: {{ $incidencia->nota_atencion }}</small>@endif</td>
                            <td>{{ $incidencia->estado }}</td>
                            @if ($esAdmin)
                                <td>
                                    <form method="POST" action="{{ route('admin.incidencias.update', $incidencia) }}" class="d-flex flex-column gap-1">
                                        @csrf @method('PATCH')
                                        <select class="form-select form-select-sm" name="estado" aria-label="Estado">
                                            @foreach ($estados as $nombre)
                                                <option value="{{ $nombre }}" @selected($incidencia->estado === $nombre)>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                        <input class="form-control form-control-sm" name="nota_atencion" placeholder="Nota de atención" maxlength="2000">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Guardar</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $esAdmin ? 7 : 6 }}" class="text-muted">No hay incidencias registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $incidencias->links() }}
    </div>
@endsection