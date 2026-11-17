<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Http\Requests\ReparacionRequest;
use App\Models\DetalleReparacion;
use App\Models\HistorialActividadUsuario;
use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Models\Repuesto;
use App\Models\Servicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use App\Models\VistaInventarioRepuestos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReparacionController extends Controller
{
    public function create(Servicio $servicio): View
    {
        $this->autorizarTecnico($servicio);

        $servicio->load(['vehiculo.cliente', 'tecnico', 'reparaciones.detalles.repuesto']);

        return view('servicios.diagnostico', [
            'servicio' => $servicio,
            'repuestos' => VistaInventarioRepuestos::query()
                ->where('stock', '>', 0)
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(ReparacionRequest $request, Servicio $servicio): RedirectResponse
    {
        $usuario = $request->user();
        $this->autorizarTecnico($servicio);
        $datos = $request->validated();

        $solicitados = collect($datos['repuestos'] ?? [])
            ->map(fn ($cantidad) => (int) $cantidad)
            ->filter(fn (int $cantidad) => $cantidad > 0);

        $reparacion = DB::transaction(function () use ($servicio, $usuario, $datos, $solicitados): Reparacion {
            $servicio = Servicio::query()
                ->with('vehiculo')
                ->whereKey($servicio->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($servicio->estado, [EstadoServicioEnum::Finalizado, EstadoServicioEnum::Entregado], true)) {
                throw ValidationException::withMessages([
                    'servicio' => 'No se puede registrar un diagnóstico en un servicio finalizado o entregado.',
                ]);
            }

            $inventario = Repuesto::query()
                ->whereIn('id_repuesto', $solicitados->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id_repuesto');

            $totalRepuestos = 0.0;
            $lineas = [];

            foreach ($solicitados as $idRepuesto => $cantidad) {
                $repuesto = $inventario->get($idRepuesto);

                if (! $repuesto) {
                    throw ValidationException::withMessages([
                        'repuestos' => 'Uno de los repuestos seleccionados no existe.',
                    ]);
                }

                if ($repuesto->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'repuestos' => 'Stock insuficiente de '.$repuesto->nombre.': disponibles '.$repuesto->stock.'.',
                    ]);
                }

                $subtotal = (float) $repuesto->precio * $cantidad;
                $totalRepuestos += $subtotal;
                $lineas[] = [$repuesto, $cantidad, $subtotal];
            }

            $reparacion = Reparacion::query()->create([
                'problema_reportado' => $datos['problema_reportado'],
                'dano_detectado' => $datos['dano_detectado'],
                'presupuesto' => (float) $datos['mano_obra'] + $totalRepuestos,
                'solucion' => $datos['solucion'],
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'Pendiente',
                'estado_presupuesto' => 'Pendiente',
                'id_servicio' => $servicio->getKey(),
            ]);

            foreach ($lineas as [$repuesto, $cantidad, $subtotal]) {
                DetalleReparacion::query()->create([
                    'id_reparacion' => $reparacion->getKey(),
                    'id_repuesto' => $repuesto->getKey(),
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal,
                ]);

                $repuesto->decrement('stock', $cantidad);
            }

            Notificacion::query()->create([
                'mensaje' => 'Servicio #'.$servicio->getKey().': hay un diagnóstico con presupuesto de $'
                    .number_format((float) $reparacion->presupuesto, 0, ',', '.').' pendiente de tu autorización.',
                'fecha_envio' => now(),
                'estado' => 'Interna',
                'id_cliente' => $servicio->vehiculo->id_cliente,
                'id_servicio' => $servicio->getKey(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'reparacion',
                'id_registro' => $reparacion->getKey(),
                'accion' => 'diagnostico_registrado',
                'descripcion' => 'Se registró el diagnóstico técnico y el presupuesto de la reparación.',
                'fecha_hora' => now(),
            ]);

            return $reparacion;
        });

        return redirect()
            ->route('servicios.show', $servicio)
            ->with('status', 'Diagnóstico #'.$reparacion->getKey().' registrado. El cliente debe autorizar el presupuesto.');
    }

    private function autorizarTecnico(Servicio $servicio): void
    {
        $usuario = request()->user();
        abort_unless($usuario instanceof Usuario, 403);

        if ($usuario->rol === RolUsuario::Administrador) {
            return;
        }

        abort_unless($usuario->rol === RolUsuario::Tecnico, 403);

        $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
        abort_unless((int) $servicio->id_tecnico === (int) $tecnico->getKey(), 403);
    }
}
