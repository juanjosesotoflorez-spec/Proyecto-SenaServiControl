@extends('layouts.app')

@section('title', 'Servicios')
@section('page-title', 'Servicios')
@section('eyebrow', 'Operación del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Registro y seguimiento</h2>
            <p>Consulta el estado de cada ingreso y su responsable técnico.</p>
        </div>
    </div>
    <form class="service-search mb-3" method="GET" action="{{ route('servicios.index') }}" role="search">
        <label class="visually-hidden" for="service-search-input">Buscar servicio</label>
        <input class="form-control" id="service-search-input" name="q" type="search" value="{{ $termino }}" placeholder="Filtrar por tipo, placa, cliente o técnico">
        <button class="btn btn-success" type="submit"><i class="fa-solid fa-magnifying-glass me-1" aria-hidden="true"></i>Buscar</button>
    </form>
    @if (auth()->user()->rol->value === 'recepcionista')
        <form method="POST" action="{{ route('servicios.store') }}" class="surface form-panel row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label" for="tipo_servicio">Tipo de servicio</label>
                <select class="form-select" id="tipo_servicio" name="tipo_servicio" required>
                    @foreach (['Mantenimiento', 'Reparacion', 'Revision general', 'Diagnostico'] as $tipo)
                        <option value="{{ $tipo }}" @selected(old('tipo_servicio') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="estado">Estado inicial</label>
                <select class="form-select" id="estado" name="estado" required>
                    <option value="{{ $estadoInicial }}" @selected(old('estado', $estadoInicial) === $estadoInicial)>{{ $estadoInicial }}</option>
                </select>
            </div>
            <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="10000" required>{{ old('descripcion') }}</textarea></div>
            <div class="col-md-4"><label class="form-label" for="fecha_ingreso">Fecha de ingreso</label><input class="form-control" id="fecha_ingreso" name="fecha_ingreso" type="date" value="{{ old('fecha_ingreso', date('Y-m-d')) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="fecha_entrega_estimada">Fecha estimada de entrega</label><input class="form-control" id="fecha_entrega_estimada" name="fecha_entrega_estimada" type="date" value="{{ old('fecha_entrega_estimada') }}"></div>
            <div class="col-md-4"><label class="form-label" for="costo">Costo</label><input class="form-control" id="costo" name="costo" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('costo', '0.00') }}" required></div>
            <div class="col-md-6">
                <label class="form-label" for="id_vehiculo">Vehículo</label>
                <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                    <option value="">Selecciona un vehículo</option>
                    @foreach ($vehiculos as $vehiculo)
                        <option value="{{ $vehiculo->id_vehiculo }}" @selected(old('id_vehiculo') == $vehiculo->id_vehiculo)>{{ $vehiculo->placa }} · {{ $vehiculo->cliente->nombre }} {{ $vehiculo->cliente->apellido }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="id_tecnico">Técnico</label>
                <select class="form-select" id="id_tecnico" name="id_tecnico" required>
                    <option value="">Selecciona un técnico</option>
                    @foreach ($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id_tecnico }}" @selected(old('id_tecnico') == $tecnico->id_tecnico)>{{ $tecnico->nombre }} {{ $tecnico->apellido }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12"><button class="btn btn-success" type="submit">Registrar servicio</button></div>
        </form>
    @endif
    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Vehículo</th><th>Cliente</th><th>Técnico</th><th>Estado</th><th>Aceptación</th>@if ($tecnicoActualId)<th>Proceso</th>@endif<th>Costo</th></tr></thead>
            <tbody>
                @forelse ($servicios as $servicio)
                    <tr>
                        <td>{{ $servicio->id_servicio }}</td>
                        <td>{{ $servicio->fecha_ingreso->format('Y-m-d') }}</td>
                        <td>{{ $servicio->tipo_servicio }}</td>
                        <td>{{ $servicio->vehiculo->placa }}</td>
                        <td>{{ $servicio->vehiculo->cliente->nombre }} {{ $servicio->vehiculo->cliente->apellido }}</td>
                        <td>{{ $servicio->tecnico->nombre }} {{ $servicio->tecnico->apellido }}</td>
                        <td><x-service-status :status="$servicio->estado" /></td>
                        <td>{{ $servicio->asignacionActual?->estado_aceptacion ?? 'Sin aceptación registrada' }}</td>
                        @if ($tecnicoActualId && (int) $servicio->id_tecnico === (int) $tecnicoActualId)
                            <td class="service-actions-cell">
                                @if ((! $servicio->asignacionActual || $servicio->asignacionActual->estado_aceptacion === 'Pendiente') && in_array($servicio->estado, [\App\Enums\EstadoServicioEnum::Pendiente, \App\Enums\EstadoServicioEnum::EnProceso], true))
                                    <form method="POST" action="{{ route('servicios.accept', $servicio) }}">
                                        @csrf
                                        <button class="btn btn-success btn-sm text-nowrap" type="submit">Aceptar orden</button>
                                    </form>
                                @elseif ($servicio->asignacionActual?->estado_aceptacion === 'Aceptada' && in_array($servicio->estado, [\App\Enums\EstadoServicioEnum::Pendiente, \App\Enums\EstadoServicioEnum::EnProceso], true))
                                    <form method="POST" action="{{ route('servicios.progress', $servicio) }}" class="service-progress-form">
                                        @csrf
                                        <label class="visually-hidden" for="estado-{{ $servicio->id_servicio }}">Nuevo estado del servicio #{{ $servicio->id_servicio }}</label>
                                        <select class="form-select form-select-sm" id="estado-{{ $servicio->id_servicio }}" name="estado" required>
                                            @if ($servicio->estado === \App\Enums\EstadoServicioEnum::Pendiente)
                                                <option value="En proceso">Iniciar proceso</option>
                                            @else
                                                <option value="En proceso">Registrar avance</option>
                                                <option value="Finalizado">Finalizar servicio</option>
                                            @endif
                                        </select>
                                        <label class="visually-hidden" for="comentario-{{ $servicio->id_servicio }}">Comentario del servicio #{{ $servicio->id_servicio }}</label>
                                        <textarea class="form-control form-control-sm" id="comentario-{{ $servicio->id_servicio }}" name="comentario" rows="2" maxlength="10000" placeholder="Describe el avance" required></textarea>
                                        <button class="btn btn-success btn-sm" type="submit">Guardar avance</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Sin acciones disponibles</span>
                                @endif
                            </td>
                        @endif
                        <td>{{ number_format((float) $servicio->costo, 2) }}</td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="{{ $tecnicoActualId ? 10 : 9 }}">No hay servicios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $servicios->links() }}
@endsection
