<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de servicios</title>
    <style>
        @page { margin: 28px 30px; }
        body { color: #111111; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 5px; color: #1B4D3E; font-size: 20px; }
        p { margin: 0; color: #56635b; }
        .report-header { margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #1B4D3E; }
        .report-total { margin-top: 6px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 8px 6px; color: #FFFFFF; background: #1B4D3E; text-align: left; }
        td { padding: 7px 6px; border-bottom: 1px solid #dce4de; }
        tbody tr:nth-child(even) { background: #F4F6F4; }
        .status { font-weight: bold; }
        .footer { margin-top: 12px; color: #64716b; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <header class="report-header">
        <h1>SenaServiControl · Reporte de servicios</h1>
        <p>Generado el {{ $generadoEn->format('d/m/Y H:i') }}</p>
        <p class="report-total">Servicios incluidos: {{ $servicios->count() }}</p>
    </header>
    <table>
        <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Placa</th><th>Cliente</th><th>Técnico</th><th>Estado</th><th>Costo</th></tr></thead>
        <tbody>
            @forelse ($servicios as $servicio)
                <tr>
                    <td>{{ $servicio->id_servicio }}</td>
                    <td>{{ $servicio->fecha_ingreso->format('d/m/Y') }}</td>
                    <td>{{ $servicio->tipo_servicio }}</td>
                    <td>{{ $servicio->vehiculo->placa }}</td>
                    <td>{{ $servicio->vehiculo->cliente->nombre }} {{ $servicio->vehiculo->cliente->apellido }}</td>
                    <td>{{ $servicio->tecnico->nombre }} {{ $servicio->tecnico->apellido }}</td>
                    <td class="status">{{ $servicio->estado->value }}</td>
                    <td>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No hay servicios registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Documento generado por SenaServiControl</p>
</body>
</html>