<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de servicios</title>
    <style>
        @page { margin: 28px 30px; }
        body { color: #17211b; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 6px; color: #278b13; font-size: 19px; }
        p { margin: 3px 0; color: #56635b; }
        .header { margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #278b13; }
        .totals { margin: 8px 0 12px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 7px 5px; color: #fff; background: #278b13; text-align: left; }
        td { padding: 6px 5px; border-bottom: 1px solid #dce4de; }
        tbody tr:nth-child(even) { background: #f3f6f3; }
        .footer { margin-top: 12px; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <header class="header">
        <h1>SenaServiControl - Reporte de servicios</h1>
        @if ($fecha_inicio && $fecha_fin)
            <p>Período: {{ \Illuminate\Support\Carbon::parse($fecha_inicio)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($fecha_fin)->format('d/m/Y') }}</p>
        @endif
        <p>Generado por: {{ $generado_por }} · {{ now()->format('d/m/Y H:i') }}</p>
        <p class="totals">Servicios: {{ $registros->count() }} · Total: ${{ number_format((float) $registros->sum('costo'), 0, ',', '.') }}</p>
    </header>
    <table>
        <thead>
            <tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Vehículo</th><th>Cliente</th><th>Técnico</th><th>Estado</th><th>Costo</th></tr>
        </thead>
        <tbody>
            @forelse ($registros as $servicio)
                <tr>
                    <td>{{ $servicio->id_servicio }}</td>
                    <td>{{ $servicio->fecha_ingreso?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td>{{ $servicio->tipo_servicio }}</td>
                    <td>{{ $servicio->vehiculo->placa ?? 'N/A' }}</td>
                    <td>{{ trim(($servicio->vehiculo->cliente->nombre ?? '') . ' ' . ($servicio->vehiculo->cliente->apellido ?? '')) ?: 'N/A' }}</td>
                    <td>{{ trim(($servicio->tecnico->nombre ?? '') . ' ' . ($servicio->tecnico->apellido ?? '')) ?: 'Sin técnico' }}</td>
                    <td>{{ $servicio->estado->value ?? $servicio->estado }}</td>
                    <td>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay servicios para el período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Documento generado por SenaServiControl</p>
</body>
</html>