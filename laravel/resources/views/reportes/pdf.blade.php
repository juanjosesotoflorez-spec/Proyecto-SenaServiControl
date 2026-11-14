<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de servicios</title>
    <style>
        @page { margin: 26px; }
        body { color: #111111; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 5px; color: #39A900; font-size: 20px; }
        p { margin: 0; color: #56635b; }
        .report-header { margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #39A900; }
        .report-total { margin-top: 6px; font-size: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        th { padding: 7px 5px; color: #FFFFFF; background: #39A900; text-align: left; }
        td { padding: 6px 5px; border-bottom: 1px solid #dce4de; vertical-align: top; overflow-wrap: break-word; }
        tbody tr:nth-child(even) { background: #F4F6F4; }
        .detail { display: block; margin-top: 3px; color: #56635b; font-size: 8px; }
        .status { font-weight: bold; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 12px; color: #64716b; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <header class="report-header">
        <h1>SenaServiControl · Reporte de servicios</h1>
        <p>Generado el {{ $generadoEn->format('d/m/Y H:i') }}</p>
        <p class="report-total">Servicios incluidos: {{ $servicios->count() }}</p>
        <p class="report-total">Valor total: ${{ number_format((float) $servicios->sum('costo'), 0, ',', '.') }}</p>
    </header>
    <table>
        <thead><tr><th>Cliente y contacto</th><th>Vehículo</th><th>Servicio realizado</th><th>Fechas</th><th>Estado</th><th class="amount">Valor</th></tr></thead>
        <tbody>
            @forelse ($servicios as $servicio)
                <tr>
                    <td>
                        {{ $servicio->vehiculo?->cliente?->nombre ?? 'Cliente no registrado' }}
                        {{ $servicio->vehiculo?->cliente?->apellido }}
                        <span class="detail">Documento: {{ $servicio->vehiculo?->cliente?->documento ?? '—' }}</span>
                        <span class="detail">Teléfono: {{ $servicio->vehiculo?->cliente?->telefono ?: '—' }}</span>
                        <span class="detail">Correo: {{ $servicio->vehiculo?->cliente?->correo ?: '—' }}</span>
                    </td>
                    <td>{{ $servicio->vehiculo?->placa ?? '—' }}</td>
                    <td>
                        {{ $servicio->tipo_servicio }}
                        @if ($servicio->descripcion)
                            <span class="detail">{{ $servicio->descripcion }}</span>
                        @endif
                    </td>
                    <td>
                        Ingreso: {{ $servicio->fecha_ingreso?->format('d/m/Y') ?? '—' }}
                        <span class="detail">Entrega estimada: {{ $servicio->fecha_entrega_estimada?->format('d/m/Y') ?? '—' }}</span>
                        <span class="detail">Entrega real: {{ $servicio->fecha_entrega_real?->format('d/m/Y') ?? '—' }}</span>
                    </td>
                    <td class="status">{{ $servicio->estado->value }}</td>
                    <td class="amount">${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No hay servicios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Documento generado por SenaServiControl</p>
</body>
</html>