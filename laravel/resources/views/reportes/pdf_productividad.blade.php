<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Productividad por técnico</title>
    <style>
        @page { margin: 28px 30px; }
        body { color: #17211b; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { margin: 0 0 6px; color: #278b13; font-size: 19px; }
        p { margin: 3px 0; color: #56635b; }
        .header { margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #278b13; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 8px 6px; color: #fff; background: #278b13; text-align: left; }
        td { padding: 7px 6px; border-bottom: 1px solid #dce4de; }
        tbody tr:nth-child(even) { background: #f3f6f3; }
        .footer { margin-top: 12px; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    <header class="header">
        <h1>SenaServiControl - Productividad por técnico</h1>
        <p>Período: {{ \Illuminate\Support\Carbon::parse($fecha_inicio)->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($fecha_fin)->format('d/m/Y') }}</p>
        <p>Generado por: {{ $generado_por }} · {{ now()->format('d/m/Y H:i') }}</p>
    </header>
    <table>
        <thead><tr><th>Técnico</th><th>Servicios atendidos</th><th>Ingresos generados</th></tr></thead>
        <tbody>
            @forelse ($registros->groupBy('id_tecnico') as $serviciosTecnico)
                @php($tecnico = $serviciosTecnico->first()->tecnico)
                <tr>
                    <td>{{ trim(($tecnico->nombre ?? '') . ' ' . ($tecnico->apellido ?? '')) ?: 'Sin técnico' }}</td>
                    <td>{{ $serviciosTecnico->count() }}</td>
                    <td>${{ number_format((float) $serviciosTecnico->sum('costo'), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No hay servicios para el período seleccionado.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>{{ $registros->count() }}</th>
                <th>${{ number_format((float) $registros->sum('costo'), 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>
    <p class="footer">Documento generado por SenaServiControl</p>
</body>
</html>