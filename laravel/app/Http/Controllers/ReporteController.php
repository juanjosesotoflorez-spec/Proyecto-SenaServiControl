<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteExportRequest;
use App\Models\Servicio;
use App\Models\Tecnico;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function index()
    {
        $servicios = $this->servicesForCurrentUser()
            ->orderByDesc('fecha_ingreso')
            ->paginate(15);
        $tecnicos = Tecnico::query()->orderBy('nombre')->orderBy('apellido')->get();

        return view('reportes.index', compact('servicios', 'tecnicos'));
    }

    public function pdf()
    {
        $servicios = $this->servicesForCurrentUser()->orderByDesc('fecha_ingreso')->get();
        $pdf = Pdf::loadView('reportes.pdf', [
            'servicios' => $servicios,
            'generadoEn' => now(),
        ]);

        return $pdf->download('reporte_clientes.pdf');
    }

    public function pdfServicios()
    {
        $servicios = $this->servicesForCurrentUser()->orderByDesc('fecha_ingreso')->get();

        return Pdf::loadView('reportes.pdf_servicios', [
            'registros' => $servicios,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'generado_por' => Auth::user()->usuario,
        ])->download('reporte_servicios.pdf');
    }

    public function print()
    {
        $servicios = $this->servicesForCurrentUser()->orderByDesc('fecha_ingreso')->get();

        return Pdf::loadView('reportes.pdf_servicios', [
            'registros' => $servicios,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'generado_por' => Auth::user()->usuario,
        ])->stream('reporte_servicios.pdf');
    }

    public function exportar(ReporteExportRequest $request)
    {
        $user = Auth::user();
        $filtros = $request->validated();
        $query = $this->servicesForCurrentUser()
            ->whereBetween('fecha_ingreso', [$filtros['fecha_inicio'], $filtros['fecha_fin']]);

        if (! empty($filtros['tecnico_id'])) {
            $query->where('id_tecnico', $filtros['tecnico_id']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        $registros = $query->orderByDesc('fecha_ingreso')->get();

        if ($filtros['formato'] === 'pdf') {
            $vistaPdf = $filtros['tipo_reporte'] === 'productividad'
                ? 'reportes.pdf_productividad'
                : 'reportes.pdf_servicios';

            $pdf = Pdf::loadView($vistaPdf, [
                'registros' => $registros,
                'fecha_inicio' => $filtros['fecha_inicio'],
                'fecha_fin' => $filtros['fecha_fin'],
                'generado_por' => $user->usuario,
            ]);

            return $pdf->download("reporte_{$filtros['tipo_reporte']}_{$filtros['fecha_inicio']}.pdf");
        }

        $filename = "reporte_{$filtros['tipo_reporte']}_{$filtros['fecha_inicio']}.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        $tipoReporte = $filtros['tipo_reporte'];

        $callback = function () use ($registros, $tipoReporte) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($tipoReporte === 'productividad') {
                fputcsv($file, ['Técnico', 'Servicios', 'Ingresos'], ';');
                foreach ($registros->groupBy('id_tecnico') as $serviciosTecnico) {
                    $tecnico = $serviciosTecnico->first()->tecnico;
                    fputcsv($file, [
                        trim(($tecnico->nombre ?? '').' '.($tecnico->apellido ?? '')) ?: 'Sin técnico',
                        $serviciosTecnico->count(),
                        $serviciosTecnico->sum('costo'),
                    ], ';');
                }
            } else {
                fputcsv($file, ['#', 'Fecha Ingreso', 'Tipo Servicio', 'Placa', 'Cliente', 'Técnico', 'Estado', 'Costo'], ';');
                foreach ($registros as $item) {
                    fputcsv($file, [
                        $item->id_servicio,
                        optional($item->fecha_ingreso)->format('Y-m-d'),
                        $item->tipo_servicio,
                        $item->vehiculo->placa ?? 'N/A',
                        trim(($item->vehiculo->cliente->nombre ?? '').' '.($item->vehiculo->cliente->apellido ?? '')),
                        trim(($item->tecnico->nombre ?? '').' '.($item->tecnico->apellido ?? '')),
                        $item->estado->value ?? $item->estado,
                        $item->costo,
                    ], ';');
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function servicesForCurrentUser(): Builder
    {
        $user = Auth::user();
        $role = $user->rol->value ?? $user->rol;
        $query = Servicio::with(['vehiculo.cliente', 'tecnico']);

        if ($role === 'tecnico') {
            $query->whereHas('tecnico', fn (Builder $tecnico) => $tecnico->where('id_usuario', $user->getKey()));
        } elseif ($role === 'cliente') {
            $query->whereHas(
                'vehiculo.cliente',
                fn (Builder $cliente) => $cliente->where('id_usuario', $user->getKey())
            );
        }

        return $query;
    }
}
