@extends('layouts.app')

@section('title', 'Detalle del servicio')
@section('page-title', 'Detalle del servicio')
@section('eyebrow', 'Ficha de atención')

@section('content')
    @php($cliente = $servicio->vehiculo->cliente)
    <div class="page-heading">
        <div>
            <h2>Servicio #{{ $servicio->id_servicio }} · {{ $servicio->tipo_servicio }}</h2>
            <p>Información del cliente, vehículo y seguimiento de la orden.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('solicitudes.index') }}">
            <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Volver a solicitudes
        </a>
    </div>

    <section class="surface p-4 mb-4" aria-labelledby="service-summary-title">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <p class="text-uppercase small fw-bold text-success mb-1">Orden de servicio</p>
                <h3 class="h4 mb-2" id="service-summary-title">{{ $servicio->tipo_servicio }} · #{{ $servicio->id_servicio }}</h3>
                <p class="text-secondary mb-0">{{ $servicio->descripcion ?: 'Sin descripción registrada.' }}</p>
            </div>
            <x-service-status :status="$servicio->estado" />
        </div>
        <hr>
        <div class="row g-3">
            <div class="col-6 col-lg-3"><span class="d-block small text-secondary">Fecha de ingreso</span><strong>{{ $servicio->fecha_ingreso?->format('d/m/Y') ?? '—' }}</strong></div>
            <div class="col-6 col-lg-3"><span class="d-block small text-secondary">Entrega estimada</span><strong>{{ $servicio->fecha_entrega_estimada?->format('d/m/Y') ?? '—' }}</strong></div>
            <div class="col-6 col-lg-3"><span class="d-block small text-secondary">Entrega real</span><strong>{{ $servicio->fecha_entrega_real?->format('d/m/Y') ?? '—' }}</strong></div>
            <div class="col-6 col-lg-3"><span class="d-block small text-secondary">Costo registrado</span><strong>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</strong></div>
        </div>
        <div class="mt-3"><span class="small text-secondary">Técnico responsable</span><strong class="d-block">{{ $servicio->tecnico->nombre }} {{ $servicio->tecnico->apellido }}</strong></div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <section class="surface p-4 h-100" aria-labelledby="client-information-title">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="user-avatar" aria-hidden="true"><i class="fa-regular fa-user"></i></span>
                    <div>
                        <p class="text-uppercase small fw-bold text-success mb-1">Datos de contacto</p>
                        <h3 class="h5 mb-0" id="client-information-title">Información del cliente</h3>
                    </div>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary">Nombre completo</dt><dd class="col-sm-8">{{ $cliente->nombre }} {{ $cliente->apellido }}</dd>
                    <dt class="col-sm-4 text-secondary">Documento</dt><dd class="col-sm-8">{{ $cliente->documento ?: 'No registrado' }}</dd>
                    <dt class="col-sm-4 text-secondary">Teléfono</dt><dd class="col-sm-8">{{ $cliente->telefono ?: 'No registrado' }}</dd>
                    <dt class="col-sm-4 text-secondary">Correo</dt><dd class="col-sm-8">{{ $cliente->correo ?: 'No registrado' }}</dd>
                    <dt class="col-sm-4 text-secondary">Dirección</dt><dd class="col-sm-8 mb-0">{{ $cliente->direccion ?: 'No registrada' }}</dd>
                </dl>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="surface p-4 h-100" aria-labelledby="vehicle-information-title">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="user-avatar" aria-hidden="true"><i class="fa-solid fa-car"></i></span>
                    <div>
                        <p class="text-uppercase small fw-bold text-success mb-1">Vehículo ingresado</p>
                        <h3 class="h5 mb-0" id="vehicle-information-title">{{ $servicio->vehiculo->placa }}</h3>
                    </div>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary">Marca y modelo</dt><dd class="col-sm-8">{{ trim(($servicio->vehiculo->marca ?? '').' '.($servicio->vehiculo->modelo ?? '')) ?: 'No registrados' }}</dd>
                    <dt class="col-sm-4 text-secondary">Año</dt><dd class="col-sm-8">{{ $servicio->vehiculo->anio ?: 'No registrado' }}</dd>
                    <dt class="col-sm-4 text-secondary">Color</dt><dd class="col-sm-8">{{ $servicio->vehiculo->color ?: 'No registrado' }}</dd>
                    <dt class="col-sm-4 text-secondary">Kilometraje</dt><dd class="col-sm-8 mb-0">{{ $servicio->vehiculo->kilometraje !== null ? number_format($servicio->vehiculo->kilometraje, 0, ',', '.').' km' : 'No registrado' }}</dd>
                </dl>
            </section>
        </div>
    </div>

    @if ($mostrarHistorialCliente)
        <section class="surface p-4 mb-4" aria-labelledby="customer-history-title">
        <div class="page-heading">
            <div>
                <h3 class="h5 mb-1" id="customer-history-title">Historial del cliente</h3>
                <p>Vehículos registrados y servicios asociados a este cliente.</p>
            </div>
        </div>
        <div class="row g-3">
            @forelse ($cliente->vehiculos as $vehiculo)
                <div class="col-12 col-lg-6">
                    <article class="border rounded-2 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h4 class="h6 mb-1">{{ $vehiculo->placa }}</h4>
                                <p class="small text-secondary mb-2">{{ trim(($vehiculo->marca ?? '').' '.($vehiculo->modelo ?? '')) ?: 'Vehículo' }}{{ $vehiculo->anio ? ' · '.$vehiculo->anio : '' }}</p>
                            </div>
                            @if ((int) $vehiculo->getKey() === (int) $servicio->vehiculo->getKey())
                                <span class="badge text-bg-success">Vehículo de esta orden</span>
                            @endif
                        </div>
                        @forelse ($vehiculo->servicios as $registro)
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-top py-2">
                                <div>
                                    <a class="fw-semibold" href="{{ route('servicios.show', $registro) }}">Servicio #{{ $registro->id_servicio }}</a>
                                    <span class="d-block small text-secondary">{{ $registro->tipo_servicio }} · {{ $registro->fecha_ingreso?->format('d/m/Y') ?? '—' }}</span>
                                </div>
                                <x-service-status :status="$registro->estado" />
                            </div>
                        @empty
                            <p class="small text-secondary border-top pt-2 mb-0">Sin servicios anteriores.</p>
                        @endforelse
                    </article>
                </div>
            @empty
                <p class="text-secondary mb-0">No hay vehículos registrados para este cliente.</p>
            @endforelse
        </div>
        </section>
    @endif

    <section class="surface p-4 mb-4" aria-labelledby="service-followup-title">
        <div class="page-heading">
            <div>
                <h3 class="h5 mb-1" id="service-followup-title">Seguimiento de la orden</h3>
                <p>Actualizaciones registradas durante la atención.</p>
            </div>
        </div>
        @forelse ($servicio->seguimientos as $seguimiento)
            <article class="border-start border-3 border-success ps-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <strong>{{ $seguimiento->fecha_hora?->format('d/m/Y H:i') ?? $seguimiento->fecha?->format('d/m/Y') ?? 'Fecha no disponible' }}</strong>
                    <x-service-status :status="$seguimiento->estado" />
                </div>
                <p class="mb-1 mt-2">{{ $seguimiento->comentario }}</p>
                @if ($seguimiento->tecnico)
                    <small class="text-secondary">Registrado por {{ $seguimiento->tecnico->nombre }} {{ $seguimiento->tecnico->apellido }}</small>
                @endif
            </article>
        @empty
            <p class="text-secondary mb-0">Aún no hay actualizaciones registradas para esta orden.</p>
        @endforelse
    </section>

    @if ($servicio->mantenimientos->isNotEmpty() || $servicio->reparaciones->isNotEmpty())
        <section class="surface p-4" aria-labelledby="work-details-title">
            <h3 class="h5 mb-3" id="work-details-title">Mantenimientos y reparaciones</h3>
            <div class="row g-3">
                @foreach ($servicio->mantenimientos as $mantenimiento)
                    <div class="col-12 col-lg-6">
                        <article class="border rounded-2 p-3 h-100">
                            <h4 class="h6">Mantenimiento · {{ $mantenimiento->tipo_mantenimiento ?: 'Sin tipo especificado' }}</h4>
                            <p class="small text-secondary mb-1">Programado: {{ $mantenimiento->fecha_programacion?->format('d/m/Y') ?? '—' }}</p>
                            <p class="small mb-1">Autorización: {{ $mantenimiento->estado_autorizacion ?: 'Pendiente' }}</p>
                            <p class="small mb-0">Trabajo: {{ $mantenimiento->estado_trabajo ?: 'Pendiente' }}{{ $mantenimiento->observaciones ? ' · '.$mantenimiento->observaciones : '' }}</p>
                        </article>
                    </div>
                @endforeach
                @foreach ($servicio->reparaciones as $reparacion)
                    <div class="col-12 col-lg-6">
                        <article class="border rounded-2 p-3 h-100">
                            <h4 class="h6">Reparación</h4>
                            <p class="small mb-1"><strong>Problema reportado:</strong> {{ $reparacion->problema_reportado ?: 'No registrado' }}</p>
                            <p class="small mb-1"><strong>Daño detectado:</strong> {{ $reparacion->dano_detectado ?: 'No registrado' }}</p>
                            <p class="small mb-1"><strong>Solución:</strong> {{ $reparacion->solucion ?: 'Pendiente' }}</p>
                            <p class="small mb-0"><strong>Presupuesto:</strong> ${{ number_format((float) $reparacion->presupuesto, 0, ',', '.') }} · {{ $reparacion->estado_presupuesto ?: 'Sin estado' }}</p>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
