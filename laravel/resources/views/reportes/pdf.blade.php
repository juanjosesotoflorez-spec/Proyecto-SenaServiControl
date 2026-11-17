<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resumen de clientes y servicios</title>
    <style>
        @page { margin: 28px 30px; }
        body { color: #111111; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0 0 5px; color: #1B4D3E; font-size: 20px; }
        p { margin: 0; color: #56635b; }
        .report-header { margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid #1B4D3E; }
        .report-total { margin-top: 6px; font-size: 9px; }
        h2 { margin: 16px 0 4px; color: #1B4D3E; font-size: 12px; }
        .client { margin-bottom: 12px; }
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
        <h1>SenaServiControl · Resumen de clientes</h1>
        <p>Generado el {{ $generadoEn->format('d/m/Y H:i') }}</p>
        <p class="report-total">Servicios incluidos: {{ $servicios->count() }} · Valor total: ${{ number_format((float) $servicios->sum('costo'), 0, ',', '.') }}</p>
    </header>
    @forelse ($servicios->groupBy(fn ($servicio) => $servicio->vehiculo->cliente->id_cliente ?? 'sin-cliente') as $serviciosCliente)
        @php($cliente = $serviciosCliente->first()->vehiculo->cliente)
        <section class="client">
            <h2>Cliente y contacto: {{ $cliente->nombre }} {{ $cliente->apellido }}</h2>
            <p>Documento: {{ $cliente->documento ?? 'N/A' }} · Correo: {{ $cliente->correo ?? 'N/A' }}</p>
            <table>
                <thead><tr><th>Fecha</th><th>Servicio realizado</th><th>Vehículo</th><th>Estado</th><th>Valor</th></tr></thead>
                <tbody>
                    @foreach ($serviciosCliente as $servicio)
                        <tr>
                            <td>{{ $servicio->fecha_ingreso?->format('d/m/Y') ?? 'N/A' }}</td>
                            <td>{{ $servicio->tipo_servicio }}</td>
                            <td>{{ $servicio->vehiculo->placa ?? 'N/A' }}</td>
                            <td>{{ $servicio->estado->value ?? $servicio->estado }}</td>
                            <td>${{ number_format((float) $servicio->costo, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p>Valor total: ${{ number_format((float) $serviciosCliente->sum('costo'), 0, ',', '.') }}</p>
        </section>
    @empty
        <p>No hay servicios registrados.</p>
    @endforelse
    <p class="footer">Documento generado por SenaServiControl</p>
</body>
</html>