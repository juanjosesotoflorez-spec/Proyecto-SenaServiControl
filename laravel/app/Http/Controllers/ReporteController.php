<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Models\Servicio;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function index(): View
    {
        $this->authorizeStaff();

        return view('reportes.index', [
            'servicios' => Servicio::query()
                ->with(['vehiculo.cliente', 'tecnico'])
                ->orderByDesc('fecha_ingreso')
                ->paginate(25),
        ]);
    }

    public function pdf(): Response
    {
        return $this->generatePdf(download: true);
    }

    public function print(): Response
    {
        return $this->generatePdf(download: false);
    }

    private function generatePdf(bool $download): Response
    {
        $usuario = request()->user();
        abort_unless($usuario instanceof Usuario && $usuario->rol === RolUsuario::Administrador, 403);

        $servicios = Servicio::query()
            ->with(['vehiculo.cliente', 'tecnico'])
            ->orderByDesc('fecha_ingreso')
            ->get();

        $pdf = Pdf::loadView('reportes.pdf', [
            'servicios' => $servicios,
            'generadoEn' => now(),
        ])
            ->setPaper('a4', 'landscape');
        $filename = 'reporte-servicios-'.now()->format('Y-m-d').'.pdf';

        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }

    private function authorizeStaff(): void
    {
        $usuario = request()->user();

        abort_unless(
            $usuario instanceof Usuario
                && in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true),
            403
        );
    }
}
