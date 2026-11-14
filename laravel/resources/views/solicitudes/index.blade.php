@extends('layouts.app')

@section('title', $esCliente ? 'Solicitar servicio' : 'Solicitudes de servicio')
@section('page-title', $esCliente ? 'Solicitar servicio' : 'Solicitudes de servicio')
@section('eyebrow', $esCliente ? 'Atención para tu vehículo' : 'Bandeja de atención')

@section('content')
    <div class="page-heading">
        <div>
            <h2>{{ $esCliente ? 'Nueva solicitud' : ($esTecnico ? 'Solicitudes por aceptar' : 'Solicitudes recibidas') }}</h2>
            <p>
                @if ($esCliente)
                    Propón una fecha y describe qué necesita tu vehículo. Un técnico revisará la solicitud.
                @elseif ($esTecnico)
                    Al aceptar, se crea la orden y queda asignada a tu perfil para iniciar el proceso.
                @else
                    Consulta las solicitudes de los clientes y el servicio al que fueron vinculadas.
                @endif
            </p>
        </div>
    </div>

    @if ($esCliente)
        @if ($vehiculos->isEmpty())
            <div class="alert alert-warning" role="status">Registra primero un vehículo para solicitar servicio.</div>
            <a class="btn btn-success mb-4" href="{{ route('vehiculos.index') }}">Registrar vehículo</a>
        @else
            <form method="POST" action="{{ route('solicitudes.store') }}" class="surface form-panel row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label" for="id_vehiculo">Vehículo</label>
                    <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                        <option value="">Selecciona tu vehículo</option>
                        @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id_vehiculo }}" @selected(old('id_vehiculo') == $vehiculo->id_vehiculo)>
                                {{ $vehiculo->placa }} · {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="tipo_servicio">Servicio solicitado</label>
                    <select class="form-select" id="tipo_servicio" name="tipo_servicio" required>
                        <option value="">Selecciona una opción</option>
                        @foreach (['Mantenimiento', 'Reparacion', 'Revision general', 'Diagnostico'] as $tipo)
                            <option value="{{ $tipo }}" @selected(old('tipo_servicio') === $tipo)>{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="fecha_solicitada">Fecha preferida</label>
                    <input class="form-control" id="fecha_solicitada" name="fecha_solicitada" type="date" min="{{ today()->toDateString() }}" max="{{ today()->addYear()->toDateString() }}" value="{{ old('fecha_solicitada') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="2000" required>{{ old('descripcion') }}</textarea>
                </div>
                <div class="col-12"><button class="btn btn-success" type="submit">Enviar solicitud</button></div>
            </form>
        @endif
    @endif

    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Solicitud</th>
                    @unless ($esCliente)<th>Cliente</th>@endunless
                    <th>Vehículo</th><th>Tipo</th><th>Fecha preferida</th><th>Descripción</th><th>Estado</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($solicitudes as $solicitud)
                    <tr>
                        <td>#{{ $solicitud->id_solicitud }}<br><small>{{ $solicitud->fecha_solicitud->format('d/m/Y H:i') }}</small></td>
                        @unless ($esCliente)
                            <td>{{ $solicitud->vehiculo->cliente->nombre }} {{ $solicitud->vehiculo->cliente->apellido }}</td>
                        @endunless
                        <td>{{ $solicitud->vehiculo->placa }}</td>
                        <td>{{ $solicitud->tipo_servicio }}</td>
                        <td>{{ $solicitud->fecha_solicitada->format('d/m/Y') }}</td>
                        <td class="request-description">{{ $solicitud->descripcion }}</td>
                        <td>
                            @if ($solicitud->estado === 'Pendiente')
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @else
                                <span class="badge bg-success">{{ $solicitud->estado }}</span>
                            @endif
                            @if ($solicitud->servicio)
                                <br><x-service-status class="mt-1" :status="$solicitud->servicio->estado" />
                            @endif
                        </td>
                        <td>
                            @if ($esTecnico && $solicitud->estado === 'Pendiente' && $solicitud->id_servicio === null)
                                <form method="POST" action="{{ route('solicitudes.accept', $solicitud) }}">
                                    @csrf
                                    <button class="btn btn-success btn-sm text-nowrap" type="submit">Aceptar solicitud</button>
                                </form>
                            @elseif ($solicitud->servicio)
                                <a class="btn btn-outline-secondary btn-sm text-nowrap" href="{{ route('servicios.show', $solicitud->servicio) }}">Ver servicio #{{ $solicitud->servicio->id_servicio }}</a>
                            @else
                                <span class="text-muted small">Pendiente de aceptación</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="{{ $esCliente ? 7 : 8 }}">No hay solicitudes para mostrar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $solicitudes->links() }}
@endsection