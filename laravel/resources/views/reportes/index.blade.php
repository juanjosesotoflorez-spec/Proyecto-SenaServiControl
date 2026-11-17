@extends('layouts.app')

@section('title', 'Reportes')
@section('page-title', 'Reportes')
@section('eyebrow', 'Información del taller')

@section('content')
    <div class="page-heading">
        <div>
            <h2>Reporte de servicios</h2>
            <p>Consulta los ingresos, responsables y estado de cada servicio.</p>
        </div>

        @if (in_array(auth()->user()->rol->value, ['administrador', 'recepcionista'], true))
            <div class="report-actions" aria-label="Acciones de reportes">
                <a class="btn btn-success" href="{{ route('reportes.pdf.servicios') }}">
                    <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                    <span>Descargar PDF</span>
                </a>
                <a class="btn btn-outline-success" href="{{ route('reportes.pdf.print') }}" target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-print" aria-hidden="true"></i>
                    <span>Imprimir</span>
                </a>
                <a class="btn btn-outline-success" href="{{ route('reportes.pdf') }}">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    <span>Resumen de clientes</span>
                </a>
            </div>
        @endif
    </div>

    <section class="surface report-filter-panel mb-4" aria-labelledby="report-filter-title">
        <div class="report-filter-heading">
            <div>
                <h3 id="report-filter-title">Filtros y exportación</h3>
                <p>Selecciona el período y los criterios para generar tu reporte.</p>
            </div>
            <span class="report-filter-icon" aria-hidden="true">
                <i class="fa-solid fa-sliders"></i>
            </span>
        </div>

        <form method="POST" action="{{ route('reportes.exportar') }}" class="row g-3 align-items-end report-filter-grid">
            @csrf
            <div class="col-lg-4 col-md-6">
                <label class="form-label" for="tipo-reporte">Tipo de reporte</label>
                <select id="tipo-reporte" name="tipo_reporte" class="form-select" required>
                    <option value="operativo" @selected(old('tipo_reporte', 'operativo') === 'operativo')>Operativo por Período</option>
                    <option value="productividad" @selected(old('tipo_reporte') === 'productividad')>Productividad por Técnico</option>
                    <option value="vehiculos" @selected(old('tipo_reporte') === 'vehiculos')>Estado de Vehículos</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label" for="fecha-inicio">Fecha inicial</label>
                <input id="fecha-inicio" type="date" name="fecha_inicio" class="form-control" value="{{ old('fecha_inicio', now()->startOfMonth()->toDateString()) }}" required>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label" for="fecha-fin">Fecha final</label>
                <input id="fecha-fin" type="date" name="fecha_fin" class="form-control" value="{{ old('fecha_fin', now()->toDateString()) }}" required>
            </div>
            <div class="col-lg-4 col-md-6">
                <label class="form-label" for="formato">Formato de descarga</label>
                <select id="formato" name="formato" class="form-select" required>
                    <option value="pdf" @selected(old('formato', 'pdf') === 'pdf')>PDF</option>
                    <option value="excel" @selected(old('formato') === 'excel')>Excel (CSV)</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="estado">Estado del servicio</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\EstadoServicioEnum::cases() as $estado)
                        <option value="{{ $estado->value }}" @selected(old('estado') === $estado->value)>{{ $estado->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label" for="tecnico-id">Técnico responsable</label>
                <select id="tecnico-id" name="tecnico_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($tecnicos as $tecnico)
                        <option value="{{ $tecnico->id_tecnico }}" @selected((string) old('tecnico_id') === (string) $tecnico->id_tecnico)>
                            {{ $tecnico->nombre }} {{ $tecnico->apellido }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-6 d-grid">
                <button type="submit" class="btn btn-success report-submit-btn">
                    <i class="fa-solid fa-file-export" aria-hidden="true"></i>
                    <span>Generar reporte</span>
                </button>
            </div>
        </form>
        @if ($errors->any())
            <div class="alert alert-danger mt-3 mb-0" role="alert">
                {{ $errors->first() }}
            </div>
        @endif
    </section>

    <section class="surface report-table-panel" aria-labelledby="report-table-title">
        <div class="report-table-heading">
            <div>
                <h3 id="report-table-title">Servicios registrados</h3>
                <p>Consulta el detalle de los trabajos del taller.</p>
            </div>
            <span class="report-count">{{ $servicios->total() }} {{ $servicios->total() === 1 ? 'servicio' : 'servicios' }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Vehículo</th>
                        <th>Cliente</th>
                        <th>Técnico</th>
                        <th>Estado</th>
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servicios as $servicio)
                        <tr>
                            <td>{{ $servicio->id_servicio }}</td>
                            <td>{{ $servicio->fecha_ingreso->format('d/m/Y') }}</td>
                            <td>{{ $servicio->tipo_servicio }}</td>
                            <td>{{ $servicio->vehiculo->placa ?? 'N/A' }}</td>
                            <td>{{ $servicio->vehiculo->cliente->nombre ?? 'N/A' }} {{ $servicio->vehiculo->cliente->apellido ?? '' }}</td>
                            <td>{{ $servicio->tecnico->nombre ?? 'N/A' }} {{ $servicio->tecnico->apellido ?? '' }}</td>
                            <td><x-service-status :status="$servicio->estado" /></td>
                            <td>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="8">No hay servicios para incluir en el reporte.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    {{ $servicios->links() }}
@endsection