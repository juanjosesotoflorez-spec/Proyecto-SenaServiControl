@extends('layouts.app')

@section('title', 'Diagnóstico técnico')
@section('page-title', 'Diagnóstico técnico')
@section('eyebrow', 'Servicio #'.$servicio->id_servicio)

@section('content')
    <div class="page-heading">
        <div>
            <h2>{{ $servicio->tipo_servicio }} · {{ $servicio->vehiculo->placa }}</h2>
            <p>Cliente: {{ $servicio->vehiculo->cliente->nombre }} {{ $servicio->vehiculo->cliente->apellido }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('servicios.show', $servicio) }}">Volver al servicio</a>
    </div>

    <form method="POST" action="{{ route('servicios.diagnostico.store', $servicio) }}" class="surface form-panel row g-3" novalidate>
        @csrf
        <div class="col-12">
            <label class="form-label" for="problema_reportado">Problema reportado</label>
            <textarea class="form-control @error('problema_reportado') is-invalid @enderror" id="problema_reportado" name="problema_reportado" rows="2" required>{{ old('problema_reportado') }}</textarea>
            @error('problema_reportado')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="dano_detectado">Daño detectado</label>
            <textarea class="form-control @error('dano_detectado') is-invalid @enderror" id="dano_detectado" name="dano_detectado" rows="2" required>{{ old('dano_detectado') }}</textarea>
            @error('dano_detectado')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="solucion">Solución propuesta</label>
            <textarea class="form-control @error('solucion') is-invalid @enderror" id="solucion" name="solucion" rows="2" required>{{ old('solucion') }}</textarea>
            @error('solucion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="observaciones">Observaciones (opcional)</label>
            <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="2">{{ old('observaciones') }}</textarea>
            @error('observaciones')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="mano_obra">Mano de obra ($)</label>
            <input class="form-control @error('mano_obra') is-invalid @enderror" id="mano_obra" name="mano_obra" type="number" min="0" step="1" value="{{ old('mano_obra', 0) }}" required>
            @error('mano_obra')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <h3 class="h6 mb-2">Repuestos del inventario</h3>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Repuesto</th><th>Precio</th><th>Stock</th><th style="width:130px">Cantidad</th></tr></thead>
                    <tbody>
                        @forelse ($repuestos as $repuesto)
                            <tr>
                                <td>{{ $repuesto->nombre }}</td>
                                <td>${{ number_format((float) $repuesto->precio, 0, ',', '.') }}</td>
                                <td>{{ $repuesto->stock }}</td>
                                <td>
                                    <input class="form-control form-control-sm" type="number" min="0" max="{{ $repuesto->stock }}"
                                           name="repuestos[{{ $repuesto->id_repuesto }}]" value="{{ old('repuestos.'.$repuesto->id_repuesto, 0) }}"
                                           aria-label="Cantidad de {{ $repuesto->nombre }}">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No hay repuestos con stock disponible.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('repuestos')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <button class="btn btn-success" type="submit"><i class="fa-solid fa-floppy-disk me-1" aria-hidden="true"></i>Registrar diagnóstico</button>
        </div>
    </form>

    @if ($servicio->reparaciones->isNotEmpty())
        <div class="surface mt-4 p-3">
            <h3 class="h6 mb-3">Diagnósticos registrados</h3>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>#</th><th>Daño detectado</th><th>Presupuesto</th><th>Autorización</th><th>Repuestos</th></tr></thead>
                    <tbody>
                        @foreach ($servicio->reparaciones as $reparacion)
                            <tr>
                                <td>{{ $reparacion->id_reparacion }}</td>
                                <td>{{ $reparacion->dano_detectado }}</td>
                                <td>${{ number_format((float) $reparacion->presupuesto, 0, ',', '.') }}</td>
                                <td>{{ $reparacion->estado_presupuesto }}</td>
                                <td>
                                    @forelse ($reparacion->detalles as $detalle)
                                        {{ $detalle->cantidad }} × {{ $detalle->repuesto->nombre }}@if (! $loop->last), @endif
                                    @empty
                                        —
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection