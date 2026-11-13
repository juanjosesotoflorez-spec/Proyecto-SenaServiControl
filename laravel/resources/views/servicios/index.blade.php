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
        <form method="POST" action="{{ route('servicios.store') }}" class="surface form-panel row g-3" data-validate novalidate>
            @csrf
            <div class="col-md-6 form-field">
                <label class="form-label" for="tipo_servicio">Tipo de servicio</label>
                <select class="form-select @error('tipo_servicio') is-invalid @enderror" id="tipo_servicio" name="tipo_servicio" required>
                    <option value="">Selecciona un tipo de servicio</option>
                    @foreach (['Mantenimiento', 'Reparacion', 'Revision general', 'Diagnostico'] as $tipo)
                        <option value="{{ $tipo }}" @selected(old('tipo_servicio') === $tipo)>{{ $tipo }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('tipo_servicio') d-block @enderror" data-validation-feedback>@error('tipo_servicio'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="estado">Estado inicial</label>
                <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                    <option value="{{ $estadoInicial }}" @selected(old('estado', $estadoInicial) === $estadoInicial)>{{ $estadoInicial }}</option>
                </select>
                <div class="invalid-feedback @error('estado') d-block @enderror" data-validation-feedback>@error('estado'){{ $message }}@enderror</div>
            </div>
            <div class="col-12 form-field">
                <label class="form-label" for="descripcion">Descripción</label>
                <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="3" maxlength="10000" required aria-describedby="descripcion-error">{{ old('descripcion') }}</textarea>
                <div class="invalid-feedback @error('descripcion') d-block @enderror" id="descripcion-error" data-validation-feedback>@error('descripcion'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-4 form-field">
                <label class="form-label" for="fecha_ingreso">Fecha de ingreso</label>
                <input class="form-control @error('fecha_ingreso') is-invalid @enderror" id="fecha_ingreso" name="fecha_ingreso" type="date" max="9999-12-31" value="{{ old('fecha_ingreso', date('Y-m-d')) }}" required aria-describedby="fecha-ingreso-error">
                <div class="invalid-feedback @error('fecha_ingreso') d-block @enderror" id="fecha-ingreso-error" data-validation-feedback>@error('fecha_ingreso'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-4 form-field">
                <label class="form-label" for="fecha_entrega_estimada">Fecha estimada de entrega</label>
                <input class="form-control @error('fecha_entrega_estimada') is-invalid @enderror" id="fecha_entrega_estimada" name="fecha_entrega_estimada" type="date" min="{{ old('fecha_ingreso', date('Y-m-d')) }}" max="9999-12-31" value="{{ old('fecha_entrega_estimada') }}" aria-describedby="fecha-entrega-error">
                <div class="invalid-feedback @error('fecha_entrega_estimada') d-block @enderror" id="fecha-entrega-error" data-validation-feedback>@error('fecha_entrega_estimada'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-4 form-field">
                <label class="form-label" for="costo">Costo</label>
                <input class="form-control @error('costo') is-invalid @enderror" id="costo" name="costo" type="text" inputmode="numeric" minlength="1" maxlength="8" min="0" max="99999999" pattern="[0-9]+" value="{{ old('costo', '0') }}" data-digits-only required aria-describedby="costo-error">
                <div class="invalid-feedback @error('costo') d-block @enderror" id="costo-error" data-validation-feedback>@error('costo'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="id_vehiculo">Vehículo</label>
                <select class="form-select @error('id_vehiculo') is-invalid @enderror" id="id_vehiculo" name="id_vehiculo" required>
                    <option value="">Selecciona un vehículo</option>
                    @foreach ($vehiculos as $vehiculo)
                        <option value="{{ $vehiculo->id_vehiculo }}" @selected(old('id_vehiculo') == $vehiculo->id_vehiculo)>{{ $vehiculo->placa }} · {{ $vehiculo->cliente->nombre }} {{ $vehiculo->cliente->apellido }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('id_vehiculo') d-block @enderror" data-validation-feedback>@error('id_vehiculo'){{ $message }}@enderror</div>
            </div>
            <div class="col-md-6 form-field">
                <label class="form-label" for="id_tecnico">Técnico</label>
                <select class="form-select @error('id_tecnico') is-invalid @enderror" id="id_tecnico" name="id_tecnico" required>
                    <option value="">Selecciona un técnico</option>
                    @foreach ($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id_tecnico }}" @selected(old('id_tecnico') == $tecnico->id_tecnico)>{{ $tecnico->nombre }} {{ $tecnico->apellido }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback @error('id_tecnico') d-block @enderror" data-validation-feedback>@error('id_tecnico'){{ $message }}@enderror</div>
            </div>
            <div class="col-12"><button class="btn btn-success" type="submit">Registrar servicio</button></div>
        </form>
    @endif
    <div class="surface table-panel table-responsive">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Vehículo</th><th>Cliente</th><th>Técnico</th><th>Estado</th><th>Aceptación</th>@if ($tecnicoActualId)<th>Gestión del servicio</th>@endif<th>Costo</th><th>Entrega</th></tr></thead>
            <tbody>
                @forelse ($servicios as $servicio)
                    <tr>
                        <td><a href="{{ route('servicios.show', $servicio) }}" aria-label="Ver detalle del servicio {{ $servicio->id_servicio }}">{{ $servicio->id_servicio }}</a></td>
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
                                    <div class="service-workflow-card service-workflow-pending">
                                        <span class="service-workflow-kicker">Asignación pendiente</span>
                                        <p>Confirma la orden para habilitar el registro de avances.</p>
                                        <form method="POST" action="{{ route('servicios.accept', $servicio) }}">
                                            @csrf
                                            <button class="btn btn-success btn-sm w-100" type="submit">
                                                <i class="fa-solid fa-check me-2" aria-hidden="true"></i>Aceptar orden
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($servicio->asignacionActual?->estado_aceptacion === 'Aceptada' && in_array($servicio->estado, [\App\Enums\EstadoServicioEnum::Pendiente, \App\Enums\EstadoServicioEnum::EnProceso], true))
                                    <button
                                        class="service-process-toggle"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#service-progress-modal-{{ $servicio->id_servicio }}"
                                        aria-haspopup="dialog"
                                        aria-controls="service-progress-modal-{{ $servicio->id_servicio }}"
                                        aria-label="Actualizar proceso del servicio #{{ $servicio->id_servicio }}"
                                    >
                                        <span class="service-process-icon"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span>
                                        <span class="service-process-title">
                                            <strong>Actualizar proceso</strong>
                                            <small>Registrar estado y avance</small>
                                        </span>
                                        <i class="fa-solid fa-arrow-up-right-from-square service-process-chevron" aria-hidden="true"></i>
                                    </button>
                                @else
                                    <div class="service-workflow-card service-workflow-complete">
                                        <span class="service-workflow-kicker"><i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Proceso cerrado</span>
                                        <p>No hay acciones pendientes para esta orden.</p>
                                    </div>
                                @endif
                            </td>
                        @endif
                        <td>{{ number_format((float) $servicio->costo, 2) }}</td>
                        <td>
                            @if ($servicio->entregas->isNotEmpty())
                                @php($entrega = $servicio->entregas->first())
                                <span class="badge text-bg-success">Registrada</span>
                                <span class="d-block small">{{ $entrega->fecha_salida?->format('d/m/Y H:i') ?? 'Fecha no disponible' }}</span>
                                @if ($entrega->recepcionista)
                                    <span class="d-block small">{{ $entrega->recepcionista->nombre }} {{ $entrega->recepcionista->apellido }}</span>
                                @endif
                            @elseif (in_array(auth()->user()->rol->value, ['administrador', 'recepcionista'], true) && in_array($servicio->estado, [\App\Enums\EstadoServicioEnum::Finalizado, \App\Enums\EstadoServicioEnum::Entregado], true))
                                <form method="POST" action="{{ route('servicios.delivery.store', $servicio) }}" data-validate novalidate>
                                    @csrf
                                    @if (auth()->user()->rol->value === 'administrador')
                                        <div class="form-field mb-2">
                                            <label class="visually-hidden" for="id-recepcionista-entrega-{{ $servicio->id_servicio }}">Recepcionista responsable</label>
                                            <select class="form-select form-select-sm @error('id_recepcionista') is-invalid @enderror" id="id-recepcionista-entrega-{{ $servicio->id_servicio }}" name="id_recepcionista" required>
                                                <option value="">Recepcionista</option>
                                                @foreach ($recepcionistas as $recepcionista)
                                                    <option value="{{ $recepcionista->id_recepcionista }}" @selected(old('id_recepcionista') == $recepcionista->id_recepcionista)>{{ $recepcionista->nombre }} {{ $recepcionista->apellido }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback @error('id_recepcionista') d-block @enderror" data-validation-feedback>@error('id_recepcionista'){{ $message }}@enderror</div>
                                        </div>
                                    @endif
                                    @if (auth()->user()->rol->value !== 'administrador' || $recepcionistas->isNotEmpty())
                                        <button class="btn btn-success btn-sm text-nowrap" type="submit">
                                            {{ $servicio->estado === \App\Enums\EstadoServicioEnum::Entregado ? 'Completar registro' : 'Registrar entrega' }}
                                        </button>
                                    @else
                                        <span class="small text-muted">No hay recepcionistas registrados.</span>
                                    @endif
                                </form>
                            @else
                                <span class="text-muted small">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="{{ $tecnicoActualId ? 11 : 10 }}">No hay servicios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $servicios->links() }}

    @if ($tecnicoActualId)
        @foreach ($servicios as $servicio)
            @if (
                (int) $servicio->id_tecnico === (int) $tecnicoActualId
                && $servicio->asignacionActual?->estado_aceptacion === 'Aceptada'
                && in_array($servicio->estado, [\App\Enums\EstadoServicioEnum::Pendiente, \App\Enums\EstadoServicioEnum::EnProceso], true)
            )
                @php($formularioConError = (string) old('form_service_id') === (string) $servicio->id_servicio)
                @php($errorEstado = $formularioConError ? $errors->first('estado') : '')
                @php($errorComentario = $formularioConError ? $errors->first('comentario') : '')
                <div
                    class="modal fade service-progress-modal"
                    id="service-progress-modal-{{ $servicio->id_servicio }}"
                    tabindex="-1"
                    aria-labelledby="service-progress-title-{{ $servicio->id_servicio }}"
                    aria-hidden="true"
                    @if ($formularioConError) data-open-service-modal="true" @endif
                >
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <p class="text-uppercase small fw-bold text-success mb-1">Seguimiento técnico</p>
                                    <h2 class="modal-title" id="service-progress-title-{{ $servicio->id_servicio }}">Actualizar proceso</h2>
                                </div>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Cerrar ventana"></button>
                            </div>
                            <form method="POST" action="{{ route('servicios.progress', $servicio) }}" class="modal-body service-progress-form" data-validate novalidate>
                                @csrf
                                <input type="hidden" name="form_service_id" value="{{ $servicio->id_servicio }}">
                                <div class="service-process-meta">
                                    <span>Orden #{{ $servicio->id_servicio }}</span>
                                    <x-service-status :status="$servicio->estado" />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="estado-{{ $servicio->id_servicio }}">Estado del servicio</label>
                                    <select class="form-select {{ $errorEstado !== '' ? 'is-invalid' : '' }}" id="estado-{{ $servicio->id_servicio }}" name="estado" required>
                                        @if ($servicio->estado === \App\Enums\EstadoServicioEnum::Pendiente)
                                            <option value="En proceso">Iniciar proceso</option>
                                        @else
                                            <option value="En proceso">Registrar avance</option>
                                            <option value="Finalizado">Finalizar servicio</option>
                                        @endif
                                    </select>
                                    <div class="invalid-feedback {{ $errorEstado !== '' ? 'd-block' : '' }}" data-validation-feedback>{{ $errorEstado }}</div>
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="comentario-{{ $servicio->id_servicio }}">Detalle del avance</label>
                                    <textarea class="form-control {{ $errorComentario !== '' ? 'is-invalid' : '' }}" id="comentario-{{ $servicio->id_servicio }}" name="comentario" rows="4" maxlength="10000" placeholder="Describe el trabajo realizado o el siguiente paso" required>{{ $formularioConError ? old('comentario') : '' }}</textarea>
                                    <div class="invalid-feedback {{ $errorComentario !== '' ? 'd-block' : '' }}" data-validation-feedback>{{ $errorComentario }}</div>
                                </div>
                                <p class="service-process-hint"><i class="fa-regular fa-bell me-1" aria-hidden="true"></i>El cliente recibirá una notificación con este avance.</p>
                                <div class="modal-footer">
                                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                                    <button class="btn btn-success" type="submit">
                                        <i class="fa-solid fa-floppy-disk me-2" aria-hidden="true"></i>Guardar avance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@endsection
