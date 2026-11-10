<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $usuario = request()->user();

        abort_unless($usuario instanceof Usuario, 403);

        $esPersonal = in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true);
        $servicios = Servicio::query();

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $servicios->whereHas('vehiculo', fn ($query) => $query->where('id_cliente', $cliente->getKey()));
        } elseif ($usuario->rol === RolUsuario::Tecnico) {
            $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $servicios->where('id_tecnico', $tecnico->getKey());
        }

        $inicioMes = now()->startOfMonth()->toDateString();
        $finMes = now()->endOfMonth()->toDateString();

        return view('dashboard.index', [
            'usuario' => $usuario,
            'esPersonal' => $esPersonal,
            'serviciosActivos' => (clone $servicios)
                ->whereNotIn('estado', [EstadoServicioEnum::Finalizado->value, EstadoServicioEnum::Entregado->value])
                ->count(),
            'serviciosEntregados' => (clone $servicios)
                ->where('estado', EstadoServicioEnum::Entregado->value)
                ->count(),
            'ingresosMes' => $esPersonal
                ? (clone $servicios)->whereBetween('fecha_ingreso', [$inicioMes, $finMes])->sum('costo')
                : null,
            'clientesRegistrados' => $esPersonal ? Cliente::query()->count() : null,
            'serviciosRecientes' => (clone $servicios)
                ->with(['vehiculo.cliente', 'tecnico'])
                ->orderByDesc('fecha_ingreso')
                ->limit(5)
                ->get(),
        ]);
    }
}
