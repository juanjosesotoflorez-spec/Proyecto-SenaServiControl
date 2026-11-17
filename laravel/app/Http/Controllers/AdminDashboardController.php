<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Http\Requests\IncidenciaRequest;
use App\Models\HistorialActividadUsuario;
use App\Models\Incidencia;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    private const CERRADOS = ['Finalizado', 'Entregado'];

    // RF-081 a RF-090: indicadores estadísticos
    public function index(): View
    {
        $this->soloAdministrador();

        $porEstado = DB::table('servicio')
            ->selectRaw('estado, COUNT(*) AS total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $cargaTecnicos = DB::table('servicio AS s')
            ->join('tecnico AS t', 't.id_tecnico', '=', 's.id_tecnico')
            ->whereNotIn('s.estado', self::CERRADOS)
            ->selectRaw('t.nombre, t.apellido, COUNT(*) AS total')
            ->groupBy('t.id_tecnico', 't.nombre', 't.apellido')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'estados' => collect(EstadoServicioEnum::cases())
                ->mapWithKeys(fn (EstadoServicioEnum $e) => [$e->value => (int) ($porEstado[$e->value] ?? 0)]),
            'ingresos' => (float) DB::table('servicio')->where('estado', EstadoServicioEnum::Entregado->value)->sum('costo'),
            'atrasados' => $this->consultaAtrasados()->count(),
            'incidenciasAbiertas' => Incidencia::query()->where('estado', '!=', 'Resuelta')->count(),
            'cargaTecnicos' => $cargaTecnicos,
        ]);
    }

    // RF-091 a RF-095: monitoreo de retrasos
    public function monitoreo(): View
    {
        $this->soloAdministrador();

        return view('admin.monitoreo', [
            'servicios' => $this->consultaAtrasados()
                ->with(['vehiculo.cliente', 'tecnico'])
                ->orderBy('fecha_entrega_estimada')
                ->paginate(20),
        ]);
    }

    // RF-096 a RF-098: incidencias
    public function incidencias(Request $request): View
    {
        $usuario = $this->usuarioInterno();
        $estado = $request->query('estado');

        $incidencias = Incidencia::query()
            ->with(['usuarioReporta', 'usuarioAtendio', 'servicio'])
            ->when(in_array($estado, IncidenciaRequest::ESTADOS, true), fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_registro')
            ->paginate(20)
            ->withQueryString();

        return view('admin.incidencias', [
            'incidencias' => $incidencias,
            'estado' => $estado,
            'estados' => IncidenciaRequest::ESTADOS,
            'esAdmin' => $usuario->rol === RolUsuario::Administrador,
            'servicios' => Servicio::query()->orderByDesc('id_servicio')->limit(100)->get(['id_servicio', 'tipo_servicio']),
        ]);
    }

    public function storeIncidencia(IncidenciaRequest $request): RedirectResponse
    {
        $usuario = $this->usuarioInterno();
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $usuario): void {
            $incidencia = Incidencia::query()->create([
                'id_usuario_reporta' => $usuario->getKey(),
                'id_servicio' => $datos['id_servicio'] ?? null,
                'descripcion' => $datos['descripcion'],
                'estado' => 'Abierta',
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $incidencia->id_servicio,
                'entidad' => 'incidencia',
                'id_registro' => $incidencia->getKey(),
                'accion' => 'incidencia_reportada',
                'descripcion' => 'Se reportó una incidencia.',
                'fecha_hora' => now(),
            ]);
        });

        return redirect()->route('admin.incidencias')->with('status', 'Incidencia reportada correctamente.');
    }

    public function updateIncidencia(IncidenciaRequest $request, Incidencia $incidencia): RedirectResponse
    {
        $usuario = $this->soloAdministrador();
        $datos = $request->validated();

        DB::transaction(function () use ($incidencia, $datos, $usuario): void {
            $incidencia->update([
                'estado' => $datos['estado'],
                'nota_atencion' => $datos['nota_atencion'] ?? $incidencia->nota_atencion,
                'id_usuario_atendio' => $usuario->getKey(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $incidencia->id_servicio,
                'entidad' => 'incidencia',
                'id_registro' => $incidencia->getKey(),
                'accion' => 'incidencia_'.str_replace(' ', '_', strtolower($datos['estado'])),
                'descripcion' => 'La incidencia pasó a estado '.$datos['estado'].'.',
                'fecha_hora' => now(),
            ]);
        });

        return redirect()->route('admin.incidencias')->with('status', 'Incidencia actualizada.');
    }

    // RF-099 y RF-100: auditoría de solo lectura (no hay rutas de edición ni borrado)
    public function auditoria(Request $request): View
    {
        $this->soloAdministrador();

        $filtros = $request->validate([
            'entidad' => ['nullable', 'string', 'max:40'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
        ]);

        $actividad = HistorialActividadUsuario::query()
            ->with(['usuario', 'servicio'])
            ->when($filtros['entidad'] ?? null, fn ($q, $v) => $q->where('entidad', $v))
            ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('fecha_hora', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('fecha_hora', '<=', $v))
            ->orderByDesc('fecha_hora')
            ->paginate(30)
            ->withQueryString();

        return view('admin.auditoria', [
            'actividad' => $actividad,
            'filtros' => $filtros,
            'entidades' => HistorialActividadUsuario::query()->distinct()->orderBy('entidad')->pluck('entidad'),
        ]);
    }

    private function consultaAtrasados()
    {
        return Servicio::query()
            ->whereNotIn('estado', self::CERRADOS)
            ->whereNotNull('fecha_entrega_estimada')
            ->whereDate('fecha_entrega_estimada', '<', today());
    }

    private function soloAdministrador(): Usuario
    {
        $usuario = request()->user();
        abort_unless($usuario instanceof Usuario && $usuario->rol === RolUsuario::Administrador, 403);

        return $usuario;
    }

    private function usuarioInterno(): Usuario
    {
        $usuario = request()->user();
        abort_unless($usuario instanceof Usuario && $usuario->rol !== RolUsuario::Cliente, 403);

        return $usuario;
    }
}