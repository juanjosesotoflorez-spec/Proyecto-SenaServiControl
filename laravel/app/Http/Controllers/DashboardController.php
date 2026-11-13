<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\SolicitudServicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $usuario = request()->user();

        abort_unless($usuario instanceof Usuario, 403);

        $esPersonal = in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true);
        $servicios = Servicio::query();
        $perfilFaltante = null;
        $clienteId = null;

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->first();
            if ($cliente) {
                $clienteId = $cliente->getKey();
                $servicios->whereHas('vehiculo', fn ($query) => $query->where('id_cliente', $cliente->getKey()));
            } else {
                $perfilFaltante = 'cliente';
                $servicios->whereRaw('1 = 0');
            }
        } elseif ($usuario->rol === RolUsuario::Tecnico) {
            $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->first();
            if ($tecnico) {
                $servicios->where('id_tecnico', $tecnico->getKey());
            } else {
                $perfilFaltante = 'técnico';
                $servicios->whereRaw('1 = 0');
            }
        }

        $puedeVerIndicadores = $esPersonal || $usuario->rol === RolUsuario::Tecnico;
        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();
        $graficas = $usuario->rol === RolUsuario::Administrador
            ? $this->datosGraficas()
            : null;

        return view('dashboard.index', [
            'usuario' => $usuario,
            'esPersonal' => $esPersonal,
            'perfilFaltante' => $perfilFaltante,
            'graficas' => $graficas,
            'serviciosActivos' => (clone $servicios)
                ->whereNotIn('estado', [EstadoServicioEnum::Finalizado->value, EstadoServicioEnum::Entregado->value])
                ->count(),
            'serviciosEntregados' => (clone $servicios)
                ->where('estado', EstadoServicioEnum::Entregado->value)
                ->count(),
            'misVehiculos' => $clienteId
                ? Vehiculo::query()->where('id_cliente', $clienteId)->count()
                : 0,
            'solicitudesPendientes' => $clienteId
                ? SolicitudServicio::query()
                    ->where('estado', 'Pendiente')
                    ->whereHas('vehiculo', fn ($query) => $query->where('id_cliente', $clienteId))
                    ->count()
                : 0,
            'puedeVerIndicadores' => $puedeVerIndicadores,
            'ingresosMes' => $puedeVerIndicadores
                ? (clone $servicios)->whereBetween('fecha_ingreso', [$inicioMes, $finMes])->sum('costo')
                : null,
            'clientesRegistrados' => $puedeVerIndicadores ? Cliente::query()->count() : null,
            'serviciosRecientes' => (clone $servicios)
                ->with(['vehiculo.cliente', 'tecnico'])
                ->orderByDesc('fecha_ingreso')
                ->limit(5)
                ->get(),
        ]);
    }

    private function datosGraficas(): array
    {
        $estados = DB::table('servicio')
            ->select('estado')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('estado')
            ->orderBy('estado')
            ->get();

        $primerMes = now()->startOfMonth()->subMonths(5);
        $ultimoDia = now()->toDateString();
        $mesSql = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', fecha_ingreso)"
            : "DATE_FORMAT(fecha_ingreso, '%Y-%m')";
        $totalesMensuales = DB::table('servicio')
            ->selectRaw("$mesSql as mes, COUNT(*) as servicios, COALESCE(SUM(costo), 0) as ingresos")
            ->whereBetween('fecha_ingreso', [$primerMes->toDateString(), $ultimoDia])
            ->groupByRaw($mesSql)
            ->get()
            ->keyBy('mes');

        $nombresMeses = [1 => 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $meses = collect(range(5, 0))
            ->map(fn (int $mesesAtras) => now()->startOfMonth()->subMonths($mesesAtras));

        return [
            'estados' => [
                'etiquetas' => $estados->pluck('estado')->all(),
                'valores' => $estados->pluck('total')->map(fn ($total) => (int) $total)->all(),
            ],
            'mensual' => [
                'etiquetas' => $meses
                    ->map(fn ($mes) => $nombresMeses[(int) $mes->format('n')].' '.$mes->format('Y'))
                    ->all(),
                'servicios' => $meses
                    ->map(fn ($mes) => (int) ($totalesMensuales->get($mes->format('Y-m'))?->servicios ?? 0))
                    ->all(),
                'ingresos' => $meses
                    ->map(fn ($mes) => (float) ($totalesMensuales->get($mes->format('Y-m'))?->ingresos ?? 0))
                    ->all(),
            ],
        ];
    }
}
