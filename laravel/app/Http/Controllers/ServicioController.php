<?php

namespace App\Http\Controllers;

use App\Enums\EstadoServicioEnum;
use App\Enums\RolUsuario;
use App\Http\Requests\AcceptServiceRequest;
use App\Http\Requests\RecordServiceProgressRequest;
use App\Http\Requests\ServicioRequest;
use App\Models\Cliente;
use App\Models\HistorialActividadUsuario;
use App\Models\HistorialAsignacionTecnico;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\Reparacion;
use App\Models\Recepcionista;
use App\Models\Seguimiento;
use App\Models\Servicio;
use App\Models\Tecnico;
use App\Models\Usuario;
use App\Models\Vehiculo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServicioController extends Controller
{
    public function index(): View
    {
        $usuario = request()->user();
        $tecnicoActualId = null;
        $termino = trim((string) request()->query('q', ''));
        abort_unless($usuario instanceof Usuario, 403);

        $servicios = Servicio::query()->with(['vehiculo.cliente', 'tecnico', 'asignacionActual']);

        if ($usuario->rol === RolUsuario::Cliente) {
            $cliente = Cliente::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $servicios->whereHas('vehiculo', fn ($query) => $query->where('id_cliente', $cliente->getKey()));
        } elseif ($usuario->rol === RolUsuario::Tecnico) {
            $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
            $tecnicoActualId = $tecnico->getKey();
            $servicios->where('id_tecnico', $tecnico->getKey());
        } elseif (! in_array($usuario->rol, [RolUsuario::Administrador, RolUsuario::Recepcionista], true)) {
            abort(403);
        }

        if ($termino !== '') {
            $coincidencia = '%'.$termino.'%';

            $servicios->where(function ($query) use ($coincidencia): void {
                $query->where('tipo_servicio', 'like', $coincidencia)
                    ->orWhere('descripcion', 'like', $coincidencia)
                    ->orWhereHas('vehiculo', function ($vehiculos) use ($coincidencia): void {
                        $vehiculos->where('placa', 'like', $coincidencia)
                            ->orWhereHas('cliente', function ($clientes) use ($coincidencia): void {
                                $clientes->where('nombre', 'like', $coincidencia)
                                    ->orWhere('apellido', 'like', $coincidencia)
                                    ->orWhere('documento', 'like', $coincidencia);
                            });
                    })
                    ->orWhereHas('tecnico', function ($tecnicos) use ($coincidencia): void {
                        $tecnicos->where('nombre', 'like', $coincidencia)
                            ->orWhere('apellido', 'like', $coincidencia);
                    });
            });
        }

        return view('servicios.index', [
            'servicios' => $servicios->orderByDesc('fecha_ingreso')->paginate(25)->withQueryString(),
            'termino' => $termino,
            'tecnicoActualId' => $tecnicoActualId,
            'estadoInicial' => EstadoServicioEnum::Pendiente->value,
            'vehiculos' => $usuario->rol === RolUsuario::Recepcionista
                ? Vehiculo::query()->with('cliente')->orderBy('placa')->get()
                : collect(),
            'tecnicos' => $usuario->rol === RolUsuario::Recepcionista
                ? Tecnico::query()->orderBy('apellido')->orderBy('nombre')->get()
                : collect(),
        ]);
    }

    public function store(ServicioRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);

        $recepcionista = Recepcionista::query()
            ->where('id_usuario', $usuario->getKey())
            ->firstOrFail();
        $data = $request->validated();

        DB::transaction(function () use ($data, $usuario, $recepcionista): void {
            $servicio = Servicio::query()->create([
                'tipo_servicio' => $data['tipo_servicio'],
                'descripcion' => $data['descripcion'],
                'fecha_ingreso' => $data['fecha_ingreso'],
                'fecha_entrega_estimada' => $data['fecha_entrega_estimada'] ?? null,
                'estado' => EstadoServicioEnum::Pendiente->value,
                'costo' => $data['costo'],
                'id_vehiculo' => $data['id_vehiculo'],
                'id_tecnico' => $data['id_tecnico'],
                'id_recepcionista' => $recepcionista->getKey(),
            ]);

            HistorialAsignacionTecnico::query()->create([
                'id_servicio' => $servicio->getKey(),
                'id_tecnico_anterior' => null,
                'id_tecnico_nuevo' => $data['id_tecnico'],
                'id_recepcionista' => $recepcionista->getKey(),
                'motivo' => 'Asignación inicial al registrar el servicio.',
            ]);

            Seguimiento::query()->create([
                'fecha' => now()->toDateString(),
                'fecha_hora' => now(),
                'estado' => EstadoServicioEnum::Pendiente->value,
                'comentario' => 'Ingreso del servicio. Trabajo solicitado: '.$data['descripcion'],
                'id_servicio' => $servicio->getKey(),
                'id_tecnico' => null,
                'aprendiz' => '',
            ]);

            $vehiculo = Vehiculo::query()->findOrFail($data['id_vehiculo']);
            Notificacion::query()->create([
                'mensaje' => 'Se registró el ingreso del servicio #'.$servicio->getKey().'. Estado inicial: '.EstadoServicioEnum::Pendiente->value.'.',
                'fecha_envio' => now(),
                'estado' => 'Interna',
                'id_cliente' => $vehiculo->id_cliente,
                'id_servicio' => $servicio->getKey(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'servicio',
                'id_registro' => $servicio->getKey(),
                'accion' => 'servicio_creado',
                'descripcion' => 'Recepción registró un nuevo servicio.',
                'fecha_hora' => now(),
            ]);
        });

        return redirect()->route('servicios.index')->with('status', 'Servicio registrado correctamente.');
    }

    public function acceptAssignment(AcceptServiceRequest $request, Servicio $servicio): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);
        $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->firstOrFail();

        DB::transaction(function () use ($servicio, $usuario, $tecnico): void {
            $servicio = Servicio::query()->whereKey($servicio->getKey())->lockForUpdate()->firstOrFail();
            $asignacion = HistorialAsignacionTecnico::query()
                ->where('id_servicio', $servicio->getKey())
                ->orderByDesc('id_asignacion')
                ->lockForUpdate()
                ->first();

            if (
                (int) $servicio->id_tecnico !== (int) $tecnico->getKey()
                || in_array($servicio->estado, [EstadoServicioEnum::Finalizado, EstadoServicioEnum::Entregado], true)
            ) {
                throw ValidationException::withMessages([
                    'asignacion' => 'Esta asignación ya fue respondida, no está vigente o pertenece a otro técnico.',
                ]);
            }

            if (! $asignacion) {
                $asignacion = HistorialAsignacionTecnico::query()->create([
                    'id_servicio' => $servicio->getKey(),
                    'id_tecnico_anterior' => null,
                    'id_tecnico_nuevo' => $tecnico->getKey(),
                    'motivo' => 'Aceptación de una asignación existente anterior al registro de historial.',
                    'estado_aceptacion' => 'Aceptada',
                    'fecha_aceptacion' => now(),
                ]);
            } else {
                if (
                    (int) $asignacion->id_tecnico_nuevo !== (int) $tecnico->getKey()
                    || $asignacion->estado_aceptacion !== 'Pendiente'
                ) {
                    throw ValidationException::withMessages([
                        'asignacion' => 'Esta asignación ya fue respondida o pertenece a otro técnico.',
                    ]);
                }

                $asignacion->update([
                    'estado_aceptacion' => 'Aceptada',
                    'fecha_aceptacion' => now(),
                ]);
            }

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'asignacion_tecnico',
                'id_registro' => $asignacion->getKey(),
                'accion' => 'asignacion_aceptada',
                'descripcion' => 'El técnico aceptó la asignación y ya puede registrar avances.',
                'fecha_hora' => now(),
            ]);
        });

        return redirect()->route('servicios.index')->with('status', 'Asignación aceptada. Ya puedes registrar avances del servicio.');
    }

    public function recordProgress(RecordServiceProgressRequest $request, Servicio $servicio): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario instanceof Usuario, 403);
        $tecnico = Tecnico::query()->where('id_usuario', $usuario->getKey())->firstOrFail();
        $datos = $request->validated();
        $nuevoEstado = EstadoServicioEnum::from($datos['estado']);

        DB::transaction(function () use ($servicio, $usuario, $tecnico, $datos, $nuevoEstado): void {
            $servicio = Servicio::query()
                ->with('vehiculo')
                ->whereKey($servicio->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $asignacion = HistorialAsignacionTecnico::query()
                ->where('id_servicio', $servicio->getKey())
                ->orderByDesc('id_asignacion')
                ->lockForUpdate()
                ->first();

            if (
                ! $asignacion
                || (int) $servicio->id_tecnico !== (int) $tecnico->getKey()
                || (int) $asignacion->id_tecnico_nuevo !== (int) $tecnico->getKey()
                || $asignacion->estado_aceptacion !== 'Aceptada'
                || in_array($servicio->estado, [EstadoServicioEnum::Finalizado, EstadoServicioEnum::Entregado], true)
            ) {
                throw ValidationException::withMessages([
                    'servicio' => 'Debes tener una asignación vigente y aceptada para registrar avances.',
                ]);
            }

            if ($nuevoEstado === EstadoServicioEnum::Finalizado && $servicio->estado !== EstadoServicioEnum::EnProceso) {
                throw ValidationException::withMessages([
                    'estado' => 'El servicio debe estar en proceso antes de finalizarlo.',
                ]);
            }

            $mantenimientosSinAutorizar = Mantenimiento::query()
                ->where('id_servicio', $servicio->getKey())
                ->where(fn ($query) => $query->whereNull('estado_autorizacion')->orWhere('estado_autorizacion', '!=', 'Aprobada'));
            $reparacionesSinAutorizar = Reparacion::query()
                ->where('id_servicio', $servicio->getKey())
                ->where(fn ($query) => $query->whereNull('estado_presupuesto')->orWhere('estado_presupuesto', '!=', 'Aprobada'));

            if (
                in_array($nuevoEstado, [EstadoServicioEnum::EnProceso, EstadoServicioEnum::Finalizado], true)
                && ($mantenimientosSinAutorizar->exists() || $reparacionesSinAutorizar->exists())
            ) {
                throw ValidationException::withMessages([
                    'estado' => 'No puedes iniciar el trabajo hasta que el cliente autorice el mantenimiento o el presupuesto.',
                ]);
            }

            if ($nuevoEstado === EstadoServicioEnum::Finalizado) {
                $mantenimientosIncompletos = Mantenimiento::query()
                    ->where('id_servicio', $servicio->getKey())
                    ->where(fn ($query) => $query->whereNull('estado_trabajo')->orWhere('estado_trabajo', '!=', 'Completado'))
                    ->exists();
                $reparacionesIncompletas = Reparacion::query()
                    ->where('id_servicio', $servicio->getKey())
                    ->where(fn ($query) => $query
                        ->whereNull('estado_presupuesto')
                        ->orWhere('estado_presupuesto', '!=', 'Aprobada')
                        ->orWhereNull('estado')
                        ->orWhere('estado', '!=', 'Completada'))
                    ->exists();

                if ($mantenimientosIncompletos || $reparacionesIncompletas) {
                    throw ValidationException::withMessages([
                        'estado' => 'No se puede finalizar: hay mantenimientos o reparaciones pendientes.',
                    ]);
                }
            }

            if ($servicio->estado !== $nuevoEstado) {
                $servicio->estado = $nuevoEstado;
                $servicio->save();
            }

            Seguimiento::query()->create([
                'fecha' => today(),
                'fecha_hora' => now(),
                'estado' => $nuevoEstado->value,
                'comentario' => $datos['comentario'],
                'id_servicio' => $servicio->getKey(),
                'id_tecnico' => $tecnico->getKey(),
                'aprendiz' => $datos['aprendiz'] ?? '',
            ]);

            Notificacion::query()->create([
                'mensaje' => 'Servicio #'.$servicio->getKey().': '.$nuevoEstado->value.'. '.$datos['comentario'],
                'fecha_envio' => now(),
                'estado' => 'Interna',
                'id_cliente' => $servicio->vehiculo->id_cliente,
                'id_servicio' => $servicio->getKey(),
            ]);

            HistorialActividadUsuario::query()->create([
                'id_usuario' => $usuario->getKey(),
                'id_servicio' => $servicio->getKey(),
                'entidad' => 'servicio',
                'id_registro' => $servicio->getKey(),
                'accion' => 'seguimiento_tecnico',
                'descripcion' => 'El técnico registró un avance con estado '.$nuevoEstado->value.'.',
                'fecha_hora' => now(),
            ]);
        });

        return redirect()->route('servicios.index')->with('status', 'Avance registrado y notificación enviada al cliente.');
    }
}
