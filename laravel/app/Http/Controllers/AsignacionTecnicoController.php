<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Http\Requests\AsignacionTecnicoRequest;
use App\Models\Administrador;
use App\Models\HistorialActividadUsuario;
use App\Models\HistorialAsignacionTecnico;
use App\Models\Notificacion;
use App\Models\Recepcionista;
use App\Models\Servicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AsignacionTecnicoController extends Controller
{
    public function edit(Servicio $servicio): View
    {
        $this->autorizar();

        $servicio->load([
            'vehiculo.cliente',
            'tecnico',
            'asignacionesTecnico' => fn ($query) => $query
                ->with(['tecnicoAnterior', 'tecnicoNuevo'])
                ->orderByDesc('id_asignacion'),
        ]);

        return view('servicios.asignacion', [
            'servicio' => $servicio,
            'tecnicos' => Tecnico::query()->orderBy('apellido')->orderBy('nombre')->get(),
        ]);
    }

    public function store(AsignacionTecnicoRequest $request, Servicio $servicio): RedirectResponse
    {
        $usuario = $this->autorizar();
        $datos = $request->validated();

        DB::transaction(function () use ($servicio, $usuario, $datos): void {
            $servicio = Servicio::query()
                ->with('vehiculo')
                ->whereKey($servicio->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($servicio->estado, [EstadoServicioEnum::Finalizado, EstadoServicioEnum::Entregado], true)) {
                throw ValidationException::withMessages([
                    'id_tecnico_nuevo' => 'No se puede reasignar un servicio finalizado o entregado.',
                ]);
            }

            if ((int) $servicio->id_tecnico === (int) $datos['id_tecnico_nuevo']) {
                throw ValidationException::withMessages([
                    'id_tecnico_nuevo' => 'El servicio ya está asignado a ese técnico.',
                ]);
            }

            $anterior = $servicio->id_tecnico;

            $asignacion = HistorialAsignacionTecnico::query()->create([
                'id_servicio' => $servicio->getKey(),
                'id_tecnico_anterior' => $anterior,
                'id_tecnico_nuevo' => $datos['id_tecnico_nuevo'],
                'id_recepcionista' => $usuario->rol === RolUsuario::Recepcionista
                    ? Recepcionista::query()->where('id_usuario', $usuario->getKey())->value('id_recepcionista')
                    : null,
                'id_administrador' => $usuario->rol === RolUsuario::Administrador
                    ? Administrador::query()->where('id_usuario', $usuario->getKey())->value('id_administrador')
                    : null,
                'motivo' => $datos['motivo'],
                'estado_aceptacion' => 'Pendiente',
            ]);

            $servicio->update(['id_tecnico' => $datos['id_tecnico_nuevo']]);

            Notificacion::query()->create([
                'mensaje' => 'Servicio #'.$servicio->getKey().': se asignó un nuevo técnico responsable.',
                'fecha_envio' => now(),
                'estado' => 'Interna',
                'id_cliente' => $servicio->vehiculo->id_cliente,
                'id_servicio' => $servicio->getKey(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'asignacion_tecnico',
                'id_registro' => $asignacion->getKey(),
                'accion' => $anterior ? 'tecnico_reasignado' : 'tecnico_asignado',
                'descripcion' => 'Se asignó el técnico #'.$datos['id_tecnico_nuevo'].'. Motivo: '.$datos['motivo'],
                'fecha_hora' => now(),
            ]);
        });

        return redirect()
            ->route('servicios.asignacion.edit', $servicio)
            ->with('status', 'Técnico asignado. Queda pendiente de su aceptación.');
    }

    private function autorizar(): Usuario
    {
        $usuario = request()->user();
        abort_unless(
            $usuario instanceof Usuario
            && in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true),
            403
        );

        return $usuario;
    }
}
