<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteExportRequest;
use App\Models\Servicio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $rol = $user->rol->value ?? $user->rol;
        $query = Servicio::with(['vehiculo.cliente', 'tecnico'])->orderByDesc('fecha_ingreso');

        if ($rol === 'tecnico') {
            $query->where('id_tecnico', $user->id_usuario ?? $user->id);
        } elseif ($rol === 'cliente') {
            $query->whereHas('vehiculo.cliente', fn($q) => $q->where('id_usuario', $user->id_usuario ?? $user->id));
        }

        $servicios = $query->paginate(15);
        return view('reportes.index', compact('servicios'));
    }

    public function exportar(ReporteExportRequest $request)
    {
        $user = Auth::user();
        $rol = $user->rol->value ?? $user->rol;

        $query = Servicio::with(['vehiculo.cliente', 'tecnico'])
            ->whereBetween('fecha_ingreso', [$request->fecha_inicio, $request->fecha_fin]);

        if ($rol === 'tecnico') {
            $query->where('id_tecnico', $user->id_usuario ?? $user->id);
        }

        $registros = $query->orderByDesc('fecha_ingreso')->get();

        if ($request->formato === 'pdf') {
            $vistaPdf = $request->tipo_reporte === 'productividad'
                ? 'reportes.pdf_productividad'
                : 'reportes.pdf_servicios';

            $pdf = Pdf::loadView($vistaPdf, [
                'registros'    => $registros,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'generado_por' => "{$user->nombre} {$user->apellido}",
            ]);

            return $pdf->download("reporte_{$request->tipo_reporte}_{$request->fecha_inicio}.pdf");
        }

        $filename = "reporte_{$request->tipo_reporte}_{$request->fecha_inicio}.csv";
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($registros) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['#', 'Fecha Ingreso', 'Tipo Servicio', 'Placa', 'Cliente', 'Tecnico', 'Estado', 'Costo'], ';');

            foreach ($registros as $item) {
                fputcsv($file, [
                    $item->id_servicio,
                    optional($item->fecha_ingreso)->format('Y-m-d'),
                    $item->tipo_servicio,
                    $item->vehiculo->placa ?? 'N/A',
                    trim(($item->vehiculo->cliente->nombre ?? '') . ' ' . ($item->vehiculo->cliente->apellido ?? '')),
                    trim(($item->tecnico->nombre ?? '') . ' ' . ($item->tecnico->apellido ?? '')),
                    $item->estado->value ?? $item->estado,
                    $item->costo,
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}